<?php

namespace humhub\modules\calendar\controllers;

use humhub\modules\calendar\helpers\dav\UserPassAuthBackend;
use Sabre\CalDAV\CalendarRoot;
use Sabre\DAVACL\PrincipalCollection;
use Yii;
use yii\helpers\Url;
use yii\web\Controller;
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

class RemoteController extends Controller
{
    public function actionCalDav($token = null)
    {
        $principalBackend = new PrincipalBackend();
        $calendarBackend = new CalendarBackend();

        $tree = [
            new PrincipalCollection($principalBackend),
            new CalendarRoot($principalBackend, $calendarBackend),
        ];

        $server = new Server($tree);

//        $server->setBaseUri(Url::to(['/calendar/remote/cal-dav', 'token' => $token]));
        $server->setBaseUri(Url::to(['/calendar/remote/cal-dav']));

        $server->addPlugin(new AuthPlugin(new UserPassAuthBackend()));
        $server->addPlugin(new DAVPlugin);
        $server->addPlugin(new SchedulePlugin());
        $server->addPlugin(new SharingPlugin());
        $server->addPlugin(new CalDAVPlugin());
        $aclPlugin = new ACLPlugin();
        $aclPlugin->adminPrincipals[] = 'principals/admin';
        $aclPlugin->setDefaultAcl([
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
        ]);
        $server->addPlugin($aclPlugin);

        if (YII_DEBUG) {
            $server->addPlugin(new BrowserPlugin());
        }
        $server->start();
        Yii::$app->response->isSent = true;
    }
}