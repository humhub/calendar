<?php

namespace humhub\modules\calendar\controllers;

use DateTime;
use Yii;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\calendar\models\CalendarEntry;

/**
 * ViewController displays the calendar on spaces or user profiles.
 *
 * @package humhub.modules_core.calendar.controllers
 * @author luke
 */
class ViewController extends ContentContainerController
{

    public $hideSidebar = true;

    public function actionIndex()
    {
        $canAddEntries = $this->contentContainer->permissionManager->can(new \humhub\modules\calendar\permissions\CreateEntry());

        return $this->render('index', [
                    'contentContainer' => $this->contentContainer,
                    'canAddEntries' => $canAddEntries
        ]);
    }

    public function actionLoadAjax()
    {
        Yii::$app->response->format = 'json';

        $output = array();

        $startDate = new DateTime(Yii::$app->request->get('start'));
        $endDate = new DateTime(Yii::$app->request->get('end'));

        $entries = CalendarEntry::getContainerEntriesByOpenRange($startDate, $endDate, $this->contentContainer);

        foreach ($entries as $entry) {
            $output[] = $entry->getFullCalendarArray();
        }

        return $output;
    }

}
