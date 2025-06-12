<?php

namespace calendar;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
*/
class FunctionalTester extends \FunctionalTester
{
    use _generated\FunctionalTesterActions;

    /**
     * Define custom actions here
     */

    public function createCalendarEntry($container, $data, $attendees = [])
    {
        $entry = new CalendarEntry($container);
        $entry->setAttributes($data);
        $entry->save();

        foreach ($attendees as $attendeeId) {
            $participant = new CalendarEntryParticipant();
            $participant->calendar_entry_id = $entry->id;
            $participant->participation_state = 3;
            $participant->user_id = $attendeeId;
            $participant->save();
        }

        return $entry;
    }
}
