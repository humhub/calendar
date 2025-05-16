<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers;

use humhub\modules\calendar\helpers\dav\UserPassAuthBackend;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\user\models\forms\Login;
use humhub\modules\user\models\User;
use humhub\modules\user\models\UserFilter;
use humhub\modules\user\services\AuthClientService;
use Sabre\CalDAV\CalendarRoot;
use Sabre\DAVACL\PrincipalCollection;
use Yii;
use yii\base\Event;
use yii\filters\auth\HttpBasicAuth;
use yii\helpers\Url;
use Sabre\DAV\Server;
use Sabre\DAV\Sharing\Plugin as DAVPlugin;
use Sabre\CalDAV\SharingPlugin as SharingPlugin;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use humhub\modules\calendar\helpers\dav\CalendarBackend;
use humhub\modules\calendar\helpers\dav\PrincipalBackend;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAVACL\Plugin as ACLPlugin;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CalDAV\Schedule\Plugin as SchedulePlugin;
use yii\rest\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;
use humhub\modules\admin\permissions\ManageUsers;

class CalDavController extends Controller
{
    /**
     * @todo Temporary workaround – should be removed after the core release.
     *
     * The issue is that the current ErrorController redirects to the login page
     * instead of returning a proper 401 error when the request is sent as an API call
     * (e.g., with Accept: application/xml or application/json).
     * This workaround is needed not only for calendar module actions, but also for the `/` endpoint,
     * since some calendar clients make generic root-level requests during the sync process.
     * For this reason, the error action is overridden for the entire HumHub instance,
     * not just within the calendar module.
     */
    public function actionError()
    {
        $exception = Yii::$app->errorHandler->exception;

        // Take control of error action only when called from Calendar Clients
        if (in_array(Yii::$app->request->headers->get('Accept'), ['*/*', 'text/xml', 'application/xml'])) {
            if ($exception instanceof ForbiddenHttpException || $exception instanceof UnauthorizedHttpException) {
                $this->response->statusCode = 401;
                $this->response->content = Response::$httpStatuses[401];
            } else {
                $code = $exception->getCode() ?: 401;
                $this->response->statusCode = $code;
                $this->response->content = Response::$httpStatuses[$code];
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
                'only' => ['index'],
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

    public function actionIndex()
    {
        $principalBackend = new PrincipalBackend();
        $calendarBackend = new CalendarBackend();

        $tree = [
            new PrincipalCollection($principalBackend),
            new CalendarRoot($principalBackend, $calendarBackend),
        ];

        $server = new Server($tree);
        $server->setBaseUri(Url::to(['/calendar/cal-dav/index']));
        $server->addPlugin(new AuthPlugin(new UserPassAuthBackend()));
        $server->addPlugin(new DAVPlugin);
        $server->addPlugin(new SharingPlugin());
        $server->addPlugin(new CalDAVPlugin());
        $server->addPlugin(new SchedulePlugin());
        $aclPlugin = new ACLPlugin();
        if (Yii::$app->user->can(ManageUsers::class)) {
            $aclPlugin->adminPrincipals[] = 'principals/' . Yii::$app->user->identity->username;
        }
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
        return $this->redirect(['index']);
    }
}
