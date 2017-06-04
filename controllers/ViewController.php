<?php

namespace humhub\modules\calendar\controllers;

use DateTime;
use Yii;
use humhub\modules\calendar\permissions\CreateEntry;
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
        return $this->render('index', [
            'contentContainer' => $this->contentContainer,
            'canAddEntries' => $this->contentContainer->permissionManager->can(new CreateEntry()),
            'filters' => [],
        ]);
    }

    public function actionLoadAjax($start, $end)
    {
        $result = [];

        $filters = Yii::$app->request->get('filters', []);

        foreach (CalendarEntry::getContainerEntriesByRange(new DateTime($start), new DateTime($end), $this->contentContainer, $filters) as $entry) {
            $result[] = $entry->getFullCalendarArray();
        }

        return $this->asJson($result);
    }

}
