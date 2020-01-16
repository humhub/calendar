<?php

namespace humhub\modules\calendar\widgets;

use Yii;
use humhub\components\Widget;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\calendar\models\SnippetModuleSettings;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\calendar\helpers\Url;

/**
 * UpcomingEvents shows next events in sidebar.
 *
 * @package humhub.modules_core.calendar.widgets
 * @author luke
 */
class UpcomingEvents extends Widget
{

    /**
     * ContentContainer to limit events to. (Optional)
     *
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * How many days in future events should be shown?
     *
     * @var int
     */
    public $daysInFuture = 7;

    public function run()
    {
        $settings = SnippetModuleSettings::instantiate();
        /** @var CalendarService $calendarService */
        $calendarService = Yii::$app->getModule('calendar')->get(CalendarService::class);

        $filters = [];

        if(!$this->contentContainer) {
            $filters[] = CalendarEntryQuery::FILTER_DASHBOARD;
        }

        $calendarEntries = $calendarService->getUpcomingEntries($this->contentContainer, $settings->upcomingEventsSnippetDuration, $settings->upcomingEventsSnippetMaxItems, $filters);

        if (empty($calendarEntries)) {
            return;
        }

        return $this->render('upcomingEvents', ['calendarEntries' => $calendarEntries, 'calendarUrl' => Url::toCalendar($this->contentContainer)]);
    }

}
