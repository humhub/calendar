<?php

namespace humhub\modules\calendar\controllers;

use DateTime;
use humhub\modules\calendar\interfaces\event\AbstractCalendarQuery;
use humhub\modules\calendar\widgets\FilterType;
use humhub\modules\calendar\widgets\WallEntry;
use humhub\modules\calendar\models\fullcalendar\FullCalendar;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\permissions\CreateEntry;
use humhub\modules\content\components\ContentContainerController;
use Yii;
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
        $this->view->setViewContext(WallEntry::VIEW_CONTEXT_FULLCALENDAR);
        return $this->render('index', [
            'contentContainer' => $this->contentContainer,
            'canAddEntries' => $this->contentContainer->permissionManager->can(new CreateEntry()),
            'filters' => [],
        ]);
    }

    /**
     * Loads entries within search interval, the given string contains timezone offset.
     *
     * @param $start string search start time e.g: '2019-12-30T00:00:00+01:00'
     * @param $end string search end time e.g: '2020-02-10T00:00:00+01:00'
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionLoadAjax($start, $end)
    {
        $result = [];

        $filters = Yii::$app->request->get('filters', []);
        $types = Yii::$app->request->get('types', []);

        // Don't expand recurrence event on filter by "I'm attending" because this option is selected per each date separately
        $expand = !in_array(AbstractCalendarQuery::FILTER_PARTICIPATE, $filters);

        foreach ($this->calendarService->getCalendarItems(new DateTime($start), new DateTime($end), $filters, $this->contentContainer, null, $expand, $types) as $entry) {
            $result[] = FullCalendar::getFullCalendarArray($entry);
        }

        return $this->asJson($result);
    }

    public function actionFindFilterTypes($keyword)
    {
        return $this->asJson(FilterType::searchByContainer($keyword, $this->contentContainer));
    }
}
