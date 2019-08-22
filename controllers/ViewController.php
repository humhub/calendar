<?php

namespace humhub\modules\calendar\controllers;

use humhub\modules\calendar\CalendarUtils;
use Yii;
use DateTime;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\permissions\CreateEntry;
use humhub\modules\content\components\ContentContainerController;

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
     * @throws \yii\base\InvalidConfigException
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
            $result[] = $entry->getFullCalendarArray();
        }

        return $this->asJson($result);
    }
}
