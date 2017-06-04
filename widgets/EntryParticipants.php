<?php

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntryParticipant;

/**
 * Description of EntryParticipants
 *
 * @author luke
 */
class EntryParticipants extends Widget
{

    public $calendarEntry;

    public function run()
    {
        // Count statitics of participants
        $countAttending = CalendarEntryParticipant::find()->where(array('calendar_entry_id' => $this->calendarEntry->id, 'participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED))->count();
        $countMaybe = CalendarEntryParticipant::find()->where(array('calendar_entry_id' => $this->calendarEntry->id, 'participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE))->count();
        $countDeclined = CalendarEntryParticipant::find()->where(array('calendar_entry_id' => $this->calendarEntry->id, 'participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED))->count();


/*        $participants = User::find();
        $participants->leftJoin('calendar_entry_participant', 'user.id=calendar_entry_participant.user_id AND calendar_entry_participant.calendar_entry_id=:calendar_entry_id AND calendar_entry_participant.participation_state=:state', [
            ':calendar_entry_id' => $this->calendarEntry->id
        ]);
        $participants->where('calendar_entry_participant.id IS NOT NULL');*/



        return $this->render('participants', array(
            'calendarEntry' => $this->calendarEntry,
            'countAttending' => $countAttending,
            'countMaybe' => $countMaybe,
            'countDeclined' => $countDeclined,
            //'participants' => $participants
        ));
    }

}
