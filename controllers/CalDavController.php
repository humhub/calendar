<?php

namespace humhub\modules\calendar\controllers;

use Sabre\CalDAV\CalendarRoot;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAVACL\PrincipalCollection;
use Yii;
use yii\web\Controller;
use Sabre\DAV;
use Sabre\CalDAV;
use Sabre\DAV\Auth\Backend\AbstractBackend;
use Sabre\DAVACL\PrincipalBackend\PDO as PrincipalPDO;
use Sabre\CalDAV\Backend\PDO as CalendarPDO;
use yii\web\Response;
use humhub\modules\user\models\User;

class CalDavController extends Controller
{
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/xml; charset=utf-8');

        $pdo = Yii::$app->db->pdo;

        $authBackend = new TokenAuthBackend();

        $principalBackend = new PrincipalPDO($pdo);

        $calendarBackend = new CalendarPDO($pdo);

        $tree = [
            new PrincipalCollection($principalBackend),
            new CalendarRoot($principalBackend, $calendarBackend),
        ];

        $server = new DAV\Server($tree);

        $server->setBaseUri('/calendar/caldav');

        $server->addPlugin(new Plugin($authBackend, 'HumHub CalDAV'));
        $server->addPlugin(new CalDAV\Plugin());
        $server->addPlugin(new DAVACL\Plugin());

        $server->exec();
        return null;
    }
}