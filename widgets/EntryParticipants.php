<?php

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntryParticipant;
/**
 * Description of CalendarEntryDateWidget
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

        return $this->render('participants', array(
            'calendarEntry' => $this->calendarEntry,
            'countAttending' => $countAttending,
            'countMaybe' => $countMaybe,
            'countDeclined' => $countDeclined,
        ));
    }

}
