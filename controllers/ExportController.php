<?php

namespace humhub\modules\calendar\controllers;

use humhub\components\Controller;
use humhub\modules\calendar\models\CalendarEntry;
use Yii;
use yii\web\Response;
use Sabre\VObject;

class ExportController extends Controller
{
    public function actionExport($calendarId)
    {
        $events = CalendarEntry::find()->all();
        $ics = CalendarEntry::generateIcal($events);

        return Yii::$app->response->sendContentAsFile($ics,  'test.ics', ['mimeType' => 'text/calendar']);
    }
}