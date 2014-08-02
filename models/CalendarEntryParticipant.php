<?php

/**
 * This is the model class for table "calendar_entry_participant".
 *
 * The followings are the available columns in table 'calendar_entry_participant':
 * @property integer $id
 * @property integer $calendar_entry_id
 * @property integer $user_id
 * @property integer $participation_state
 */
class CalendarEntryParticipant extends HActiveRecord
{

    const PARTICIPATION_STATE_INVITED = 0;
    const PARTICIPATION_STATE_DECLINED = 1;
    const PARTICIPATION_STATE_MAYBE = 2;
    const PARTICIPATION_STATE_ACCEPTED = 3;

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return CalendarEntryParticipant the static model class
     */

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'calendar_entry_participant';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('calendar_entry_id, user_id', 'required'),
            array('calendar_entry_id, user_id, participation_state', 'numerical', 'integerOnly' => true),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, calendar_entry_id, user_id, participation_state', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
        );
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

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('calendar_entry_id', $this->calendar_entry_id);
        $criteria->compare('user_id', $this->user_id);
        $criteria->compare('participation_state', $this->participation_state);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

}
