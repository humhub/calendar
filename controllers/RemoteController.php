<?php

namespace humhub\modules\calendar\controllers;

use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\dav\UserPassAuthBackend;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\user\models\forms\Login;
use humhub\modules\user\models\User;
use humhub\modules\user\services\AuthClientService;
use Sabre\CalDAV\CalendarRoot;
use Sabre\CalDAV\Schedule\IMipPlugin;
use Sabre\DAVACL\PrincipalCollection;
use Yii;
use yii\base\Event;
use yii\filters\auth\HttpBasicAuth;
use yii\helpers\Inflector;
use yii\helpers\Url;
use Sabre\DAV\Server;
use Sabre\DAV\Sharing\Plugin as DAVPlugin;
use Sabre\CalDAV\SharingPlugin as SharingPlugin;
use Sabre\CalDAV\Schedule\Plugin as SchedulePlugin;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use humhub\modules\calendar\helpers\dav\CalendarBackend;
use humhub\modules\calendar\helpers\dav\PrincipalBackend;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAVACL\Plugin as ACLPlugin;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use yii\rest\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

class RemoteController extends Controller
{
    /**
     * @todo need to be removed after core release
     */
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;

        // Take control of error action only when called from Calendar Clients
        if (in_array(Yii::$app->request->headers->get('Accept'), ['text/xml', 'application/xml'])) {
            if ($exception instanceof ForbiddenHttpException || $exception instanceof UnauthorizedHttpException) {
                $this->response->statusCode = 401;
                $this->response->content = Response::$httpStatuses[401];
            } else {
                $this->response->statusCode = $exception->getCode();
                $this->response->content = Response::$httpStatuses[$exception->getCode()];
            }

            return $this->response;
        }

        // Call core error action
        return Yii::$app->runAction('error/index');
    }

    public function beforeAction($action)
    {

        if ($action->id == 'well-known') {
            // Allow `REPORT` and `PROPFIND` request for guests
            return true;
        }

        Yii::$app->on('twofa.beforeCheck', function (Event $event) use ($action) {
            $event->handled = true;
        });

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [
            [
                'class' => HttpBasicAuth::class,
                'except' => ['error'],
                'auth' => function($username, $password) {
                    $login = new Login();
                    $login->username = $username;
                    $login->password = $password;

                    if ($login->validate()) {
                        $authClientService = new AuthClientService($login->authClient);
                        $authClientService->autoMapToExistingUser();

                        return $authClientService->getUser();
                    }

                    return null;
                }
            ]
        ];
    }

    public function actionCalDav()
    {
        $principalBackend = new PrincipalBackend();
        $calendarBackend = new CalendarBackend();

        $tree = [
            new PrincipalCollection($principalBackend),
            new CalendarRoot($principalBackend, $calendarBackend),
        ];

        $server = new Server($tree);
        $server->setBaseUri(Url::to(['/calendar/remote/cal-dav']));
        $server->addPlugin(new AuthPlugin(new UserPassAuthBackend()));
        $server->addPlugin(new DAVPlugin);
        $server->addPlugin(new SchedulePlugin());
        $server->addPlugin(new SharingPlugin());
        $server->addPlugin(new CalDAVPlugin());
        $server->addPlugin(new IMipPlugin('noreply@example.org'));
        $aclPlugin = new ACLPlugin();
        $aclPlugin->adminPrincipals[] = 'principals/admin';
        /*$aclPlugin->setDefaultAcl([
            [
                'principal' => 'principals/admin',
                'privilege' => '{DAV:}read',
                'protected' => true,
            ],
            [
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}read',
                'protected' => true,
            ],
        ]);*/
        $server->addPlugin($aclPlugin);

        if (YII_DEBUG) {
            $server->addPlugin(new BrowserPlugin());
            $server->debugExceptions = true;
        }
        $server->start();
        Yii::$app->response->isSent = true;
    }

    public function actionWellKnown()
    {
        return $this->redirect(['cal-dav']);
    }

    public function actionIcal($token)
    {
        $contentContainer = ContentContainer::findOne(['guid' => $token]);

        if (!$contentContainer) {
            throw new NotFoundHttpException();
        }

        // Login as owner of the content container only for this request
        Yii::$app->user->enableSession = false;
        Yii::$app->user->login(User::findOne(['id' => $contentContainer->owner_user_id]));

        /** @var CalendarService $calendarService */
        $calendarService = Yii::$app->moduleManager->getModule('calendar')->get(CalendarService::class);

        $events = $calendarService->getCalendarItems(null, null, [], $contentContainer->polymorphicRelation);
        $ics = CalendarUtils::generateIcal($events);

        return Yii::$app->response->sendContentAsFile(
            $ics,
            Inflector::slug($contentContainer->polymorphicRelation->displayName, '_') . '.ics',
            ['mimeType' => 'text/calendar']
        );
    }
}
