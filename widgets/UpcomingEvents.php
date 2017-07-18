<?php

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\SnippetModuleSettings;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\helpers\Url;

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
        $settings = SnippetModuleSettings::instance();
        $calendarEntries = CalendarEntry::getUpcomingEntries($this->contentContainer, $settings->upcomingEventsSnippetDuration, $settings->upcomingEventsSnippetMaxItems);

        if (empty($calendarEntries)) {
            return;
        }

        $calendarUrl = ($this->contentContainer) ? $this->contentContainer->createUrl('/calendar/view') : Url::toRoute('/calendar/global');

        return $this->render('upcomingEvents', ['calendarEntries' => $calendarEntries, 'calendarUrl' => $calendarUrl]);
    }

}
