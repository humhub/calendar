<?php

namespace humhub\modules\calendar\controllers;

use Sabre\CalDAV\CalendarRoot;
use Sabre\DAVACL\PrincipalCollection;
use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use Sabre\DAV\Server;
use Sabre\DAV\Sharing\Plugin as DAVPlugin;
use Sabre\CalDAV\SharingPlugin as CalDAVPlugin;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use humhub\modules\calendar\helpers\dav\CalendarBackend;
use humhub\modules\calendar\helpers\dav\PrincipalBackend;

class RemoteController extends Controller
{
    public function actionCalDav($token)
    {
        $principalBackend = new PrincipalBackend();
        $calendarBackend = new CalendarBackend();

        $tree = [
            new PrincipalCollection($principalBackend),
            new CalendarRoot($principalBackend, $calendarBackend),
        ];

        $server = new Server($tree);

        $server->setBaseUri(Url::to(['/calendar/remote/cal-dav', 'token' => $token]));

        $server->addPlugin(new DAVPlugin);
        $server->addPlugin(new CalDAVPlugin());
        if (YII_DEBUG) {
            $server->addPlugin(new BrowserPlugin());
        }
        $server->start();
        Yii::$app->response->isSent = true;
    }
}