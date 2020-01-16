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
    // NONE means user hasn't responded or removed a previous response.
    // NONE is usually not stored explicitly, instead, no matches in
    // calendar_entry_participant implies NONE.
    const PARTICIPATION_STATE_NONE = 0;
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
        return [
            [['calendar_entry_id', 'user_id'], 'required'],
            [['calendar_entry_id', 'user_id', 'participation_state'], 'integer'],
        ];
    }

    public function showParticipantInfo()
    {
        return $this->participation_state != self::PARTICIPATION_STATE_DECLINED;
    }

    public function getCalendarEntry()
    {
        return $this->hasOne(CalendarEntry::class, ['id' => 'calendar_entry_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'calendar_entry_id' => 'Calendar Entry',
            'user_id' => 'User',
            'participation_state' => 'Participation State',
        ];
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
        } else {
            throw new \yii\base\Exception("Invalid participation state: " . $this->participation_state);
        }

        if($activity) {
            $activity->source = $this->calendarEntry;
            $activity->originator = $this->user;
            $activity->create();
        }

        return parent::afterSave($insert, $changedAttributes);
    }

}
