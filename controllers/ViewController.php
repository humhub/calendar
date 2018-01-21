<?php

namespace humhub\modules\calendar\controllers;

use DateTime;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\space\models\Space;
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

    /**
     * @inheritdoc
     */
    public $hideSidebar = true;

    /**
     * @var CalendarService
     */
    public $calendarService;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->calendarService = $this->module->get(CalendarService::class);
    }

    public function actionIndex()
    {
        return $this->render('index', [
            'contentContainer' => $this->contentContainer,
            'canAddEntries' => $this->contentContainer->permissionManager->can(new CreateEntry()),
            'canConfigure' => $this->canConfigure(),
            'filters' => [],
        ]);
    }

    public function actionLoadAjax($start, $end)
    {
        $result = [];

        $filters = Yii::$app->request->get('filters', []);

        foreach ($this->calendarService->getCalendarItems(new DateTime($start), new DateTime($end), $filters, $this->contentContainer) as $entry) {
            $result[] = $entry->getFullCalendarArray();
        }

        return $this->asJson($result);
    }

    public function canConfigure()
    {
        if ($this->contentContainer instanceof Space) {
            return $this->contentContainer->isAdmin();
        } else {
            return $this->contentContainer->isCurrentUser();
        }
    }
}
