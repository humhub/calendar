<?php

namespace humhub\modules\calendar\controllers;

use Sabre\CalDAV\CalendarRoot;
use Sabre\DAVACL\PrincipalCollection;
use yii\helpers\Url;
use yii\web\Controller;
use Sabre\DAV\Server;
use Sabre\DAV\Sharing\Plugin as DAVPlugin;
use Sabre\CalDAV\SharingPlugin as CalDAVPlugin;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use humhub\modules\calendar\helpers\dav\CalendarBackend;
use humhub\modules\calendar\helpers\dav\PrincipalBackend;

class CalDavController extends Controller
{
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

        $server->addPlugin(new DAVPlugin);
        $server->addPlugin(new CalDAVPlugin());
        $server->addPlugin(new BrowserPlugin());

        $server->start();

        return null;
    }
}