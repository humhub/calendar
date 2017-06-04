<?php

namespace humhub\modules\calendar\models;

use humhub\modules\user\models\User;
use humhub\components\ActiveRecord;
use humhub\modules\calendar\models\CalendarEntry;

/**
 * This is the model class for table "calendar_entry_participant".
 *
 * The followings are the available columns in table 'calendar_entry_participant':
 * @property integer $id
 * @property integer $calendar_entry_id
 * @property integer $user_id
 * @property integer $participation_state
 */
class CalendarEntryParticipant extends ActiveRecord
{

    const PARTICIPATION_STATE_INVITED = 0;
    const PARTICIPATION_STATE_DECLINED = 1;
    const PARTICIPATION_STATE_MAYBE = 2;
    const PARTICIPATION_STATE_ACCEPTED = 3;

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'calendar_entry_participant';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array(['calendar_entry_id', 'user_id'], 'required'),
            array(['calendar_entry_id', 'user_id', 'participation_state'], 'integer'),
        );
    }

    public function getCalendarEntry()
    {
        return $this->hasOne(CalendarEntry::className(), ['id' => 'calendar_entry_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'calendar_entry_id' => 'Calendar Entry',
            'user_id' => 'User',
            'participation_state' => 'Participation State',
        );
    }

    public function beforeDelete()
    {
        return parent::beforeDelete();
    }

    public function afterSave($insert, $changedAttributes)
    {
        $activity = null;
        if ($this->participation_state == self::PARTICIPATION_STATE_ACCEPTED) {
            $activity = new \humhub\modules\calendar\activities\ResponseAttend;
        } elseif ($this->participation_state == self::PARTICIPATION_STATE_MAYBE) {
            $activity = new \humhub\modules\calendar\activities\ResponseMaybe();
        } elseif ($this->participation_state == self::PARTICIPATION_STATE_DECLINED) {
            $activity = new \humhub\modules\calendar\activities\ResponseDeclined();
        } else if(!$this->participation_state == self::PARTICIPATION_STATE_INVITED) {
            throw new \yii\base\Exception("Invalid participation state!");
        }

        if($activity) {
            $activity->source = $this->calendarEntry;
            $activity->originator = $this->user;
            $activity->create();
        }

        return parent::afterSave($insert, $changedAttributes);
    }

}
