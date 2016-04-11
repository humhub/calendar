<?php

namespace humhub\modules\calendar\models;

use DateTime;
use DateInterval;
use Yii;
use yii\base\Exception;
use humhub\libs\DbDateValidator;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\calendar\models\CalendarEntryParticipant;

/**
 * This is the model class for table "calendar_entry".
 *
 * The followings are the available columns in table 'calendar_entry':
 * @property integer $id
 * @property string $title
 * @property string $description
 * @property string $start_datetime
 * @property string $end_datetime It is the moment immediately after the event has ended. For example, if the last full day of an event is Thursday, the exclusive end of the event will be 00:00:00 on Friday!
 * @property integer $all_day
 * @property integer $participation_mode
 * @property integer $recur
 * @property integer $recur_type
 * @property integer $recur_interval
 * @property string $recur_end
 */
class CalendarEntry extends ContentActiveRecord implements \humhub\modules\search\interfaces\Searchable
{

    // Atm not attach meetings to wall
    public $autoAddToWall = true;

    /**
     * @inheritdoc
     */
    public $wallEntryClass = "humhub\modules\calendar\widgets\WallEntry";

    /**
     * Flag for Entry Form to set this content to public
     */
    public $is_public = false;

    /**
     * This attributes are used for time input
     */
    public $selected_participants = "";

    /**
     * Times
     */
    public $start_time;
    public $end_time;

    /**
     * Participation Modes
     */
    const PARTICIPATION_MODE_NONE = 0;
    const PARTICIPATION_MODE_INVITE = 1;
    const PARTICIPATION_MODE_ALL = 2;

    /**
     * Filters
     */
    const FILTER_PARTICIPATE = 1;
    const FILTER_INVITED = 2;
    const FILTER_NOT_RESPONDED = 3;
    const FILTER_RESPONDED = 4;
    const FILTER_MINE = 5;

    public function init()
    {
        parent::init();

        /**
         * Default participiation Mode
         */
        $this->participation_mode = 2;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'calendar_entry';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'start_datetime', 'end_datetime'], 'required'],
            [['start_time', 'end_time'], 'date', 'format' => 'php:H:i'],
            [['start_datetime'], DbDateValidator::className(), 'format' => Yii::$app->params['formatter']['defaultDateFormat'], 'timeAttribute' => 'start_time'],
            [['end_datetime'], DbDateValidator::className(), 'format' => Yii::$app->params['formatter']['defaultDateFormat'], 'timeAttribute' => 'end_time'],
            [['is_public', 'all_day'], 'boolean'],
            [['title'], 'string', 'max' => 200],
            [['participation_mode'], 'in', 'range' => [self::PARTICIPATION_MODE_ALL, self::PARTICIPATION_MODE_INVITE, self::PARTICIPATION_MODE_NONE]],
            [['end_datetime'], 'validateEndTime'],
            [['description'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array(
            'id' => Yii::t('CalendarModule.base', 'ID'),
            'title' => Yii::t('CalendarModule.base', 'Title'),
            'description' => Yii::t('CalendarModule.base', 'Description'),
            'start_datetime' => Yii::t('CalendarModule.base', 'Start Date'),
            'end_datetime' => Yii::t('CalendarModule.base', 'End Date'),
            'start_time' => Yii::t('CalendarModule.base', 'Start Time'),
            'end_time' => Yii::t('CalendarModule.base', 'End Time'),
            'all_day' => Yii::t('CalendarModule.base', 'All Day'),
            'participation_mode' => Yii::t('CalendarModule.base', 'Participation Mode'),
        );
    }

    /**
     * Validator for the endtime field.
     * Execute this after DbDateValidator
     *
     * @param string $attribute attribute name
     * @param type $params parameters
     */
    public function validateEndTime($attribute, $params)
    {
        if (new \DateTime($this->start_datetime) >= new \DateTime($this->end_datetime)) {
            $this->addError($attribute, Yii::t('CalendarModule.base', "End time must be after start time!"));
        }
    }
    
    /**
     * Searches for container calendar entries with the start and/or the end date within a given range.
     * This will include all entries ending or starting within the given range.
     * @param DateTime $start start range
     * @param DateTime $end end range
     * @param ContentContainerActiveRecord $contentContainer
     * @param type $limit
     * @return type
     */
    public static function getContainerEntriesByOpenRange(DateTime $start, DateTime $end, ContentContainerActiveRecord $contentContainer, $limit = 0)
    {
        $entries = array();
        
        $query = self::find()->contentContainer($contentContainer)->readable();
        //Search for all container entries with start and/or end within the given range
        $query->andFilterWhere(
                    ['or',
                        ['and',
                            ['>=', 'start_datetime', $start->format('Y-m-d H:i:s')],
                            ['<=', 'start_datetime', $end->format('Y-m-d H:i:s')]
                        ],
                        ['and',
                            ['>=', 'end_datetime', $start->format('Y-m-d H:i:s')],
                            ['<=', 'end_datetime', $end->format('Y-m-d H:i:s')]
                        ]
                    ]
        );
        
        $query->orderBy('start_datetime ASC');

        if ($limit != 0) {
            $query->limit($limit);
        }

        foreach ($query->all() as $entry) {
            $entries[] = $entry;
        }
        return $entries;
    }

    public static function getContainerEntriesByRange(DateTime $start, DateTime $end, ContentContainerActiveRecord $contentContainer, $limit = 0)
    {
        $entries = array();

        // Limit Range to one month
        $interval = $start->diff($end);
        if ($interval->days > 50) {
            throw new Exception('Range maximum exceeded!');
        }

        $query = self::find()->contentContainer($contentContainer)->readable();
        $query->andWhere(['>=', 'start_datetime', $start->format('Y-m-d H:i:s')]);
        $query->andWhere(['<=', 'end_datetime', $end->format('Y-m-d H:i:s')]);
        $query->orderBy('start_datetime ASC');

        if ($limit != 0) {
            $query->limit($limit);
        }

        foreach ($query->all() as $entry) {
            $entries[] = $entry;
        }
        return $entries;
    }

    public static function getEntriesByRange(DateTime $start, DateTime $end, $includes = array(), $filters = array(), $limit = 0)
    {
        // Limit Range to one month
        $interval = $start->diff($end);
        if ($interval->days > 50) {
            throw new Exception('Range maximum exceeded!');
        }

        $query = self::find();
        $query->andWhere(['>=', 'start_datetime', $start->format('Y-m-d H:i:s')]);
        $query->andWhere(['<=', 'end_datetime', $end->format('Y-m-d H:i:s')]);
        $query->orderBy('start_datetime ASC');
        $query->userRelated($includes);
        $query->leftJoin('calendar_entry_participant', 'calendar_entry.id=calendar_entry_participant.calendar_entry_id AND calendar_entry_participant.user_id=:userId', [':userId' => Yii::$app->user->id]);
        $query->readable();

        // Attach filters
        if (in_array(self::FILTER_PARTICIPATE, $filters)) {
            $query->andWhere(['calendar_entry_participant.participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED]);
        }
        if (in_array(self::FILTER_INVITED, $filters)) {
            $query->andWhere(['calendar_entry_participant.participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_INVITED]);
        }
        if (in_array(self::FILTER_RESPONDED, $filters)) {
            $query->andWhere(['IS NOT', 'calendar_entry_participant.id', new \yii\db\Expression('NULL')]);
        }
        if (in_array(self::FILTER_NOT_RESPONDED, $filters)) {
            $query->andWhere(['IS', 'calendar_entry_participant.id', new \yii\db\Expression('NULL')]);
        }
        if (in_array(self::FILTER_MINE, $filters)) {
            $query->andWhere(['content.user_id' => Yii::$app->user->id]);
        }

        if ($limit != 0) {
            $query->limit($limit);
        }

        $entries = array();
        foreach ($query->all() as $entry) {
            $entries[] = $entry;
        }

        return $entries;
    }

    public static function getUpcomingEntries(ContentContainerActiveRecord $contentContainer = null, $daysInFuture = 7, $limit = 5)
    {
        $entries = array();
        $start = new DateTime();

        $query = CalendarEntry::find();
        $query->orderBy('start_datetime ASC');

        if ($daysInFuture > 0) {
            $startEnd = new DateTime();
            $startEnd->add(new DateInterval("P" . $daysInFuture . "D"));
            $query->andWhere(['>=', 'start_datetime', $start->format('Y-m-d H:i:s')]);
            $query->andWhere(['<=', 'end_datetime', $startEnd->format('Y-m-d H:i:s')]);
        } else {
            $query->andWhere(['>=', 'start_datetime', $start->format('Y-m-d H:i:s')]);
        }

        if ($contentContainer == null) {
            // When no contentcontainer is specified - limit to events where current user participate
            $query->leftJoin('calendar_entry_participant', 'calendar_entry.id=calendar_entry_participant.calendar_entry_id AND calendar_entry_participant.user_id=:userId', [':userId' => Yii::$app->user->id]);
            $query->andWhere(['IS NOT', 'calendar_entry_participant.id', new \yii\db\Expression('NULL')]);
        }

        if ($limit != 0) {
            $query->limit($limit);
        }

        if ($contentContainer !== null) {
            $query->contentContainer($contentContainer);
        }

        foreach ($query->all() as $entry) {
            if ($entry->content->canRead()) {
                $entries[] = $entry;
            }
        }
        return $entries;
    }

    public function afterFind()
    {
        parent::afterFind();

        // Load form only attributes
        $this->is_public = $this->content->visibility;
        $this->start_time = Yii::$app->formatter->asTime($this->start_datetime, 'php:H:i');
        $this->end_time = Yii::$app->formatter->asTime($this->end_datetime, 'php:H:i');
    }

    public function beforeDelete()
    {

        foreach (CalendarEntryParticipant::findAll(['calendar_entry_id' => $this->id]) as $participant) {
            $participant->delete();
        }

        return parent::beforeDelete();
    }

    public function beforeSave($insert)
    {
        $this->content->visibility = $this->is_public;

        $startDateTime = new \DateTime($this->start_datetime);
        $endDateTime = new \DateTime($this->end_datetime);

        // Check is a full day span
        if ($this->all_day == 0 && \humhub\modules\calendar\Utils::isFullDaySpan($startDateTime, $endDateTime)) {
            $this->all_day = 1;
        }

        if ($this->all_day) {
            $this->start_datetime = Yii::$app->formatter->asDateTime($startDateTime, 'php:Y-m-d') . " 00:00:00";
            $this->end_datetime = Yii::$app->formatter->asDateTime($endDateTime, 'php:Y-m-d') . " 23:59:59";
        }

        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            // Creator automatically attends to this event
            $participant = new CalendarEntryParticipant;
            $participant->user_id = Yii::$app->user->id;
            $participant->calendar_entry_id = $this->id;
            $participant->participation_state = CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED;
            $participant->save();
        }

        return;
    }

    /**
     * Returns array for FullCalendar Javascript
     *
     * @return Array information for fullcalendar
     */
    public function getFullCalendarArray()
    {
        $end = Yii::$app->formatter->asDatetime($this->end_datetime, 'php:c');

        if ($this->all_day) {
            // Note: In fullcalendar the end time is the moment AFTER the event.
            // But we store the exact event time 00:00:00 - 23:59:59 so add some time to the full day event.
            $endDateTime = new DateTime($this->end_datetime);
            $endDateTime->add(new DateInterval('PT2H'));
            $end = $endDateTime->format('Y-m-d');
        }

        return array(
            'id' => $this->id,
            'title' => $this->title,
            'editable' => $this->content->canWrite(),
            'allDay' => ($this->all_day) ? true : false,
            'updateUrl' => $this->content->container->createUrl('/calendar/entry/edit-ajax', array('id' => $this->id, 'end_datetime' => '-end-', 'start_datetime' => '-start-', 'fullCalendar' => '1')),
            'viewUrl' => $this->content->container->createUrl('/calendar/entry/view', array('id' => $this->id, 'fullCalendar' => '1')),
            'start' => Yii::$app->formatter->asDatetime($this->start_datetime, 'php:c'),
            'end' => $end,
        );
    }

    /**
     * Checks if given or current user can respond to this event
     *
     * @param User $user
     * @return boolean
     */
    public function canRespond(User $user = null)
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        if ($user == null) {
            $user = Yii::$app->user->getIdentity();
        }

        if ($this->participation_mode == self::PARTICIPATION_MODE_ALL) {
            return true;
        }

        if ($this->participation_mode == self::PARTICIPATION_MODE_INVITE) {
            $participant = CalendarEntryParticipant::findOne(['calendar_entry_id' => $this->id, 'user_id' => $user->id]);
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
        if (Yii::$app->user->isGuest) {
            return false;
        }

        if ($user == null) {
            $user = Yii::$app->user->getIdentity();
        }

        $participant = CalendarEntryParticipant::findOne(['calendar_entry_id' => $this->id, 'user_id' => $user->id]);
        if ($participant !== null) {
            return true;
        }

        return false;
    }

    public function getParticipationState(User $user = null)
    {
        if (Yii::$app->user->isGuest) {
            return 0;
        }


        if ($user == null) {
            $user = Yii::$app->user->getIdentity();
        }

        $participant = CalendarEntryParticipant::findOne(['user_id' => $user->id, 'calendar_entry_id' => $this->id]);

        if ($participant !== null) {
            return $participant->participation_state;
        }

        return 0;
    }

    /**
     * Get events duration in days
     *
     * @return int days
     */
    public function GetDurationDays()
    {

        $s = new DateTime($this->start_datetime);
        $e = new DateTime($this->end_datetime);

        $interval = $s->diff($e, true);

        return $interval->days + 1;
    }

    /**
     * @inheritdoc
     */
    public function getContentName()
    {
        return Yii::t('CalendarModule.base', "Event");
    }

    /**
     * @inheritdoc
     */
    public function getContentDescription()
    {
        return $this->title;
    }

    /**
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        return array(
            'title' => $this->title,
            'description' => $this->description,
        );
    }

}
