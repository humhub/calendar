<?php

class CalendarWallEntryWidget extends HWidget
{

    public $calendarEntry;

    public function run()
    {


        $calendarEntryParticipant = CalendarEntryParticipant::model()->findByAttributes(array('user_id' => Yii::app()->user->id, 'calendar_entry_id' => $this->calendarEntry->id));


        $this->render('wallEntry', array(
            'calendarEntry' => $this->calendarEntry,
            'calendarEntryParticipant' => $calendarEntryParticipant,
        ));
    }

}

?>