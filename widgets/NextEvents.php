<?php

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntry;

/**
 * NextEventsSidebarWidget shows next events in sidebar.
 *
 * @package humhub.modules_core.calendar.widgets
 * @author luke
 */
class NextEvents extends Widget
{

    /**
     * ContentContainer to limit events to. (Optional)
     *
     * @var HActiveRecordContentContainer
     */
    public $contentContainer;

    /**
     * How many days in future events should be shown?
     *
     * @var int
     */
    public $daysInFuture = 7;

    /**
     * Maximum Events to display
     *
     * @var int
     */
    public $maxEvents = 3;

    public function run()
    {
        $calendarEntries = CalendarEntry::getUpcomingEntries($this->contentContainer, $this->daysInFuture, $this->maxEvents);

        if (count($calendarEntries) == 0) {
            return;
        }

        return $this->render('nextEvents', array('calendarEntries' => $calendarEntries));
    }

}
