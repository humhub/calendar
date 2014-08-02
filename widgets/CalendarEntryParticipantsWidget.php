<?php

/**
 * HumHub
 * Copyright © 2014 The HumHub Project
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 */

/**
 * Description of CalendarEntryDateWidget
 *
 * @author luke
 */
class CalendarEntryParticipantsWidget extends HWidget
{

    public $calendarEntry;

    public function run()
    {
        // Count statitics of participants
        $countAttending = CalendarEntryParticipant::model()->countByAttributes(array('calendar_entry_id' => $this->calendarEntry->id, 'participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED));
        $countMaybe = CalendarEntryParticipant::model()->countByAttributes(array('calendar_entry_id' => $this->calendarEntry->id, 'participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE));
        $countDeclined = CalendarEntryParticipant::model()->countByAttributes(array('calendar_entry_id' => $this->calendarEntry->id, 'participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED));

        $this->render('participants', array(
            'calendarEntry' => $this->calendarEntry,
            'countAttending' => $countAttending,
            'countMaybe' => $countMaybe,
            'countDeclined' => $countDeclined,
        ));
    }

}
