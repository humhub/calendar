<?php

namespace humhub\modules\calendar\controllers;

use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\ReminderService;
use humhub\modules\calendar\models\FullCalendar;
use Yii;
use DateTime;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\permissions\CreateEntry;
use humhub\modules\content\components\ContentContainerController;
use yii\base\InvalidConfigException;

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

    /**
     * @return string
     * @throws InvalidConfigException
     * @throws \Throwable
     */
    public function actionIndex()
    {
        return $this->render('index', [
            'contentContainer' => $this->contentContainer,
            'canAddEntries' => $this->contentContainer->permissionManager->can(new CreateEntry()),
            'filters' => [],
        ]);
    }

    /**
     * @param $start
     * @param $end
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionLoadAjax($start, $end)
    {
        $result = [];

        $filters = Yii::$app->request->get('filters', []);

        foreach ($this->calendarService->getCalendarItems(new DateTime($start, CalendarUtils::getUserTimeZone()), new DateTime($end, CalendarUtils::getUserTimeZone()), $filters, $this->contentContainer) as $entry) {
            $result[] = FullCalendar::getFullCalendarArray($entry);;
        }

        return $this->asJson($result);
    }
}
