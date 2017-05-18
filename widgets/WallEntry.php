<?php

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\assets\Assets;
use Yii;
use humhub\modules\calendar\models\CalendarEntryParticipant;

class WallEntry extends \humhub\modules\content\widgets\WallEntry
{

    /**
     * @inheritdoc
     */
    public $editRoute = "/calendar/entry/edit";
    
    /**
     * @inheritdoc
     */
    public $editMode = self::EDIT_MODE_MODAL;
    
    /**
     * @inheritdoc
     */
    public function run()
    {
        Assets::register($this->getView());
        $calendarEntryParticipant = CalendarEntryParticipant::find()->where(['user_id' => Yii::$app->user->id, 'calendar_entry_id' => $this->contentObject->id])->one();

        return $this->render('wallEntry', [
                    'calendarEntry' => $this->contentObject,
                    'calendarEntryParticipant' => $calendarEntryParticipant,
        ]);
    }

}

?>