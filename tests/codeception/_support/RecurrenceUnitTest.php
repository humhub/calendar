<?php


namespace calendar;


use DateTime;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\calendar\models\recurrence\CalendarRecurrenceExpand;
use humhub\modules\space\models\Space;

class RecurrenceUnitTest extends CalendarUnitTest
{
    /**
     * @var Space
     */
    protected $space;

    /**
     * @var CalendarEntry
     */
    protected $rootEvent;

    /**
     * @var CalendarEntry[]
     */
    protected $recurrences;

    const DEFAULT_RRULE = 'FREQ=DAILY;INTERVAL=1';

    protected function initRecurrentEvents($rrule = self::DEFAULT_RRULE, $startDate = null, $expand = true)
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->space = Space::findOne(['id' => 1]);
        $startDate = $startDate ?: $this->getEntryDate();
        $this->rootEvent = $this->createEntry($startDate, 1, 'Past Entry', $this->space);
        $this->setDefaults($this->rootEvent, $rrule);
        $this->assertTrue($this->rootEvent->save());

        if($expand) {
            $this->recurrences = $this->expand(true);
        }
    }

    protected function setDefaults(CalendarEntry $entry, $rrule = null)
    {
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $entry->title = 'My Recurrent Event';
        $entry->description = 'My Recurrent Event Description';
        $entry->setRrule($rrule);
    }

    /**
     * @param bool $save
     * @param null $entry
     * @param int $fromDay
     * @param int $toDay
     * @return \humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF[]
     * @throws \Exception
     */
    protected function expand( $save = false, $entry = null,  $fromDay = 1, $toDay = 7)
    {
        if(!$entry) {
            $entry = $this->rootEvent;
        }
        $expandStart = (new DateTime)->setDate(2019, 12, $fromDay)->setTime(0,0,0);
        $expandEnd = (new DateTime)->setDate(2019, 12,  $toDay)->setTime(23,59,59);
        $result = [];
        return CalendarRecurrenceExpand::expand($entry, $expandStart, $expandEnd, $result, $save);
    }

    /**
     * @return DateTime
     * @throws \Exception
     */
    protected function getEntryDate()
    {
        return (new DateTime())->setDate(2019, 12, 1);
    }
}