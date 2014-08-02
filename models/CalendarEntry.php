<?php

/**
 * This is the model class for table "calendar_entry".
 *
 * The followings are the available columns in table 'calendar_entry':
 * @property integer $id
 * @property string $title
 * @property string $description
 * @property string $start_time The date/time an event begins. Required.
 * @property string $end_time It is the moment immediately after the event has ended. For example, if the last full day of an event is Thursday, the exclusive end of the event will be 00:00:00 on Friday!
 * @property integer $all_day
 * @property integer $participation_mode
 * @property integer $recur
 * @property integer $recur_type
 * @property integer $recur_interval
 * @property string $recur_end
 */
class CalendarEntry extends HActiveRecordContent
{

    /**
     * Flag for Entry Form to set this content to public
     */
    public $is_public = false;

    /**
     * This attributes are used in Edit Form for All Day Events
     */
    public $start_time_date = null;
    public $end_time_date = null;

    /**
     * Default participiation Mode
     */
    public $participation_mode = 2;

    const PARTICIPATION_MODE_NONE = 0;
    const PARTICIPATION_MODE_INVITE = 1;
    const PARTICIPATION_MODE_ALL = 2;

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return CalendarEntry the static model class
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
        return 'calendar_entry';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('title, start_time, end_time, all_day', 'required'),
            array('start_time_date, end_time_date', 'required', 'on' => 'edit'),
            array('start_time, end_time, start_time_date, end_time_date', 'date', 'format' => 'yyyy-MM-dd hh:mm:ss'),
            array('end_time', 'validateEndTime'),
            array('all_day, is_public', 'in', 'range' => array(0, 1), 'allowEmpty' => true),
            array('all_day, recur, recur_type, recur_interval', 'numerical', 'integerOnly' => true),
            array('title', 'length', 'max' => 255),
            array('participation_mode', 'in', 'range' => array(self::PARTICIPATION_MODE_ALL, self::PARTICIPATION_MODE_INVITE, self::PARTICIPATION_MODE_NONE), 'allowEmpty' => true),
            array('recur_end, description', 'safe'),
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
            'id' => Yii::t('CalendarModule.base', 'ID'),
            'title' => Yii::t('CalendarModule.base', 'Title'),
            'description' => Yii::t('CalendarModule.base', 'Description'),
            'start_time' => Yii::t('CalendarModule.base', 'Start Date and Time'),
            'end_time' => Yii::t('CalendarModule.base', 'End Date and Time'),
            'start_time_date' => Yii::t('CalendarModule.base', 'Start Date'),
            'end_time_date' => Yii::t('CalendarModule.base', 'End Date'),
            'all_day' => Yii::t('CalendarModule.base', 'All Day'),
            'participation_mode' => Yii::t('CalendarModule.base', 'Participation Mode'),
            'recur' => Yii::t('CalendarModule.base', 'Recur'),
            'recur_type' => Yii::t('CalendarModule.base', 'Recur Type'),
            'recur_interval' => Yii::t('CalendarModule.base', 'Recur Interval'),
            'recur_end' => Yii::t('CalendarModule.base', 'Recur End'),
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
        $criteria->compare('title', $this->title, true);
        $criteria->compare('description', $this->description, true);
        $criteria->compare('start_time', $this->start_time, true);
        $criteria->compare('end_time', $this->end_time, true);
        $criteria->compare('all_day', $this->all_day);
        $criteria->compare('recur', $this->recur);
        $criteria->compare('recur_type', $this->recur_type);
        $criteria->compare('recur_interval', $this->recur_interval);
        $criteria->compare('recur_end', $this->recur_end, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Validator for the endtime field
     * 
     * @param type $attr
     * @param type $options
     */
    public function validateEndTime($attribute, $params)
    {

        if ($this->start_time != "" && $this->end_time != "") {
            $s = new DateTime($this->start_time);
            $e = new DateTime($this->end_time);

            if ($s > $e) {
                $this->addError($attribute, Yii::t('CalendarModule.base', "End time must be after start time!"));
            }
        }
    }

    public static function getEntriesByRange(DateTime $start, DateTime $end, HActiveRecordContentContainer $contentContainer, $limit = 0)
    {

        // Limit Range to one month
        $interval = $start->diff($end);
        if ($interval->days > 50) {
            throw new Exception('Range maximum exceeded!');
        }

        $criteria = new CDbCriteria();
        $criteria->condition = 'start_time >= :start AND end_time <= :end';
        $criteria->params = array('start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s'));
        $criteria->order = "start_time ASC";

        if ($limit != 0) {
            $criteria->limit = $limit;
        }

        return self::getEntriesByCriteria($criteria, $contentContainer);
    }

    public static function getUpcomingEntries(HActiveRecordContentContainer $contentContainer, $daysInFuture = 7, $limit = 5)
    {
        $start = new DateTime();
        $startEnd = new DateTime();
        $startEnd->add(new DateInterval("P" . $daysInFuture . "D"));

        $criteria = new CDbCriteria();
        $criteria->condition = 'start_time >= :start AND start_time <= :end';
        $criteria->params = array('start' => $start->format('Y-m-d H:i:s'), 'end' => $startEnd->format('Y-m-d H:i:s'));
        $criteria->order = "start_time ASC";

        if ($limit != 0) {
            $criteria->limit = $limit;
        }

        return self::getEntriesByCriteria($criteria, $contentContainer);
    }

    public static function getEntriesByCriteria($criteria, HActiveRecordContentContainer $contentContainer)
    {
        $entries = array();

        foreach (CalendarEntry::model()->contentContainer($contentContainer)->findAll($criteria) as $entry) {
            if ($entry->content->canRead()) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    public function afterFind()
    {

        parent::afterFind();

        // Load Form Attributes
        $this->is_public = $this->content->visibility;
        $this->start_time_date = $this->start_time;
        $this->end_time_date = $this->end_time;
    }

    public function beforeDelete()
    {

        foreach (CalendarEntryParticipant::model()->findAll() as $participant) {
            $participant->delete();
        }

        return parent::beforeDelete();
    }

    public function beforeSave()
    {

        $this->content->visibility = $this->is_public;

        $startTime = new DateTime($this->start_time);
        $endTime = new DateTime($this->end_time);

        if (CalendarUtils::isFullDaySpan($startTime, $endTime)) {
            $this->all_day = 1;
        } else {
            $this->all_day = 0;
        }

        return parent::beforeSave();
    }

    public function afterSave()
    {

        if ($this->isNewRecord) {
            // Creator automatically attends to this event
            $participant = new CalendarEntryParticipant;
            $participant->user_id = Yii::app()->user->id;
            $participant->calendar_entry_id = $this->id;
            $participant->participation_state = CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED;
            $participant->save();
        }

        return parent::afterSave();
    }

    /**
     * Returns array for FullCalendar Javascript
     * 
     * @return Array information for fullcalendar
     */
    public function getFullCalendarArray()
    {
        $dateFormat = 'c';
        $end = $this->formatDateTime($dateFormat, $this->end_time);

        if ($this->all_day) {
            $dateFormat = 'Y-m-d';

            // Note: In fullcalendar the end time is the moment AFTER the event.
            // But we store the exact event time 00:00:00 - 23:59:59 so add some time to the full day event.
            $endDateTime = new DateTime($this->end_time);
            $endDateTime->add(new DateInterval('PT2H'));
            $end = $endDateTime->format($dateFormat);
        }

        return array(
            'id' => $this->id,
            'title' => $this->title,
            'editable' => $this->content->canWrite(),
            'allDay' => ($this->all_day) ? true : false,
            'start' => $this->formatDateTime($dateFormat, $this->start_time),
            'end' => $end,
        );
    }

    private function formatDateTime($format = 'c', $time = 'now')
    {
        $d = new DateTime($time);
        return $d->format($format);
    }

    /**
     * Returns the Wall Output
     */
    public function getWallOut()
    {
        return Yii::app()->getController()->widget('application.modules.calendar.widgets.CalendarWallEntryWidget', array('calendarEntry' => $this), true);
    }

    /**
     * Checks if given or current user can respond to this event
     * 
     * @param User $user
     * @return boolean
     */
    public function canRespond(User $user = null)
    {
        if ($user == null) {
            $user = Yii::app()->user->getModel();
        }

        if ($this->participation_mode == self::PARTICIPATION_MODE_ALL) {
            return true;
        }

        if ($this->participation_mode == self::PARTICIPATION_MODE_INVITE) {
            $participant = CalendarEntryParticipant::model()->findByAttributes(array('calendar_entry_id' => $this->id, 'user_id' => $user->id));
            if ($participant !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if given or current user already responded to this event 
     * 
     * @param User $user
     * @return boolean
     */
    public function hasResponded(User $user = null)
    {
        if ($user == null) {
            $user = Yii::app()->user->getModel();
        }

        $participant = CalendarEntryParticipant::model()->findByAttributes(array('calendar_entry_id' => $this->id, 'user_id' => $user->id));
        if ($participant !== null) {
            return true;
        }

        return false;
    }

    /**
     * Get events duration in days
     * 
     * @return int days
     */
    public function GetDurationDays()
    {

        $s = new DateTime($this->start_time);
        $e = new DateTime($this->end_time);

        $interval = $s->diff($e, true);

        return $interval->days + 1;
    }

    /**
     * Hack until this is supported
     * 
     * @param type $url
     * @param type $params
     */
    public function createContainerUrlTemp($route, $params = array())
    {
        $container = $this->content->getContainer();

        if ($container instanceof Space) {
            $params['sguid'] = $container->guid;
        } elseif ($container instanceof User) {
            $params['uguid'] = $container->guid;
        }

        return Yii::app()->createUrl($route, $params);
    }

}
