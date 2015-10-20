<?php

namespace humhub\modules\calendar\widgets;

use Yii;
use humhub\modules\calendar\models\CalendarEntryParticipant;

class WallEntry extends \humhub\modules\content\widgets\WallEntry
{

    public function run()
    {
        $calendarEntryParticipant = CalendarEntryParticipant::find()->where(array('user_id' => Yii::$app->user->id, 'calendar_entry_id' => $this->contentObject->id))->one();

        return $this->render('wallEntry', array(
                    'calendarEntry' => $this->contentObject,
                    'calendarEntryParticipant' => $calendarEntryParticipant,
        ));
    }

}

?>