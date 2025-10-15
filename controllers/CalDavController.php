<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers;

use humhub\modules\calendar\helpers\dav\CalDavAuth;
use humhub\modules\calendar\helpers\dav\UserPassAuthBackend;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\user\models\User;
use humhub\modules\user\models\UserFilter;
use Sabre\CalDAV\CalendarRoot;
use Sabre\DAVACL\PrincipalCollection;
use Yii;
use yii\base\Event;
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
use humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter;

class CalDavController extends Controller
{
    /**
     * @todo Temporary workaround – should be removed after the core release.
     * @site https://github.com/humhub/humhub-internal/issues/670
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
        $accept = Yii::$app->request->headers->get('Accept', []);

        if (is_string($accept)) {
            $accept = array_map('trim', explode(',', $accept));
            $accept = array_map(fn($type) => strtok($type, ';'), $accept);
        }

        // Take control of error action only when called from Calendar Clients
        if (
            !empty($accept)
            && $accept[0] !== 'text/html'
            && !empty(array_intersect($accept, ['*/*', 'text/xml', 'application/xml' . 'text/calendar', 'application/ics', 'text/plain']))
        ) {
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

        Yii::$app->on('twofa.beforeCheck', function (Event $event) use ($action): void {
            $event->handled = true;
        });

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [
            [
                'class' => CalDavAuth::class,
                'only' => ['index'],
            ],
        ];
    }

    public function actionIndex()
    {
        try {
            $principalBackend = new PrincipalBackend();
            $calendarBackend = new CalendarBackend();

            $tree = [
                new PrincipalCollection($principalBackend),
                new CalendarRoot($principalBackend, $calendarBackend),
            ];

            $server = new Server($tree);
            $server->setBaseUri(Url::to(['/calendar/cal-dav/index']));
            $server->addPlugin(new AuthPlugin(new UserPassAuthBackend()));
            $server->addPlugin(new DAVPlugin());
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
        } catch (\Throwable $e) {
            Yii::error($e);
            throw $e;
        }
    }

    public function actionWellKnown()
    {
        return $this->redirect(['index']);
    }
}
