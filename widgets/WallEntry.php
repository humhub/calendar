<?php

namespace humhub\modules\calendar\widgets;

use Yii;
use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntryParticipant;

class WallEntry extends Widget
{

    public $calendarEntry;

    public function run()
    {
        $calendarEntryParticipant = CalendarEntryParticipant::find()->where(array('user_id' => Yii::$app->user->id, 'calendar_entry_id' => $this->calendarEntry->id))->one();

        return $this->render('wallEntry', array(
                    'calendarEntry' => $this->calendarEntry,
                    'calendarEntryParticipant' => $calendarEntryParticipant,
        ));
    }

}

?>