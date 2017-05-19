<?php

namespace humhub\modules\calendar\models;

use humhub\modules\content\models\Content;
use humhub\modules\search\interfaces\Searchable;
use Yii;
use DateTime;
use DateInterval;
use yii\base\Exception;
use humhub\libs\DbDateValidator;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\user\models\User;

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
 * @property string $color
 */
class CalendarEntry extends ContentActiveRecord implements Searchable
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
    public $is_public = Content::VISIBILITY_PUBLIC;

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

    public function getParticipants()
    {
        return $this->hasMany(CalendarEntryParticipant::className(), ['calendar_entry_id' => 'id']);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'start_datetime', 'end_datetime'], 'required'],
            ['color', 'string'],
            [['start_time', 'end_time'], 'date', 'format' => 'php:H:i'],
            [['start_datetime'], DbDateValidator::className(), 'format' => Yii::$app->params['formatter']['defaultDateFormat'], 'timeAttribute' => 'start_time'],
            [['end_datetime'], DbDateValidator::className(), 'format' => Yii::$app->params['formatter']['defaultDateFormat'], 'timeAttribute' => 'end_time'],
            [['is_public', 'all_day'], 'integer'],
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
        return [
            'id' => Yii::t('CalendarModule.base', 'ID'),
            'title' => Yii::t('CalendarModule.base', 'Title'),
            'description' => Yii::t('CalendarModule.base', 'Description'),
            'start_datetime' => Yii::t('CalendarModule.base', 'Start Date'),
            'end_datetime' => Yii::t('CalendarModule.base', 'End Date'),
            'start_time' => Yii::t('CalendarModule.base', 'Start Time'),
            'end_time' => Yii::t('CalendarModule.base', 'End Time'),
            'all_day' => Yii::t('CalendarModule.base', 'All Day'),
            'is_public' => Yii::t('CalendarModule.base', 'Public'),
            'participation_mode' => Yii::t('CalendarModule.base', 'Participation Mode'),
        ];
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
        $this->content->visibility = $this->is_public ? Content::VISIBILITY_PUBLIC : Content::VISIBILITY_PRIVATE;

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

    public function inviteParticipant($user)
    {
        $this->setParticipant($user, CalendarEntryParticipant::PARTICIPATION_STATE_INVITED);
    }

    public function setParticipant($user, $state = CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED)
    {
        $participant = $this->findParticipant($user);

        if (!$participant) {
            $participant = new CalendarEntryParticipant;
        }

        $participant->user_id = $user->id;
        $participant->calendar_entry_id = $this->id;
        $participant->participation_state = $state;
        $participant->save();
    }

    /**
     * Finds a participant instance for the given user or the logged in user if no user provided.
     *
     * @param User $user
     * @return CalendarEntryParticipant
     */
    public function findParticipant(User $user = null)
    {
        if (!$user) {
            $user = Yii::$app->user->getIdentity();
        }

        return CalendarEntryParticipant::findOne(['user_id' => $user->id, 'calendar_entry_id' => $this->id]);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($insert) {
            $this->setParticipant(Yii::$app->user->getIdentity());
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

        return [
            'id' => $this->id,
            'title' => $this->title,
            'editable' => $this->content->canWrite(),
            'backgroundColor' => $this->color,
            'allDay' => $this->all_day,
            'updateUrl' => $this->content->container->createUrl('/calendar/entry/edit-ajax', ['id' => $this->id, 'end_datetime' => '-end-', 'start_datetime' => '-start-', 'fullCalendar' => '1']),
            'viewUrl' => $this->content->container->createUrl('/calendar/entry/view', ['id' => $this->id, 'cal' => '1']),
            'start' => Yii::$app->formatter->asDatetime($this->start_datetime, 'php:c'),
            'end' => $end,
        ];
    }

    public function getUrl()
    {
        return $this->content->container->createUrl('/calendar/entry/view', ['id' => $this->id, 'fullCalendar' => '1']);
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
    public function getDurationDays()
    {
        $s = new DateTime($this->start_datetime);
        $e = new DateTime($this->end_datetime);

        $interval = $s->diff($e, true);

        return $interval->days + 1;
    }

    /**
     * Checks if the event is currently running.
     */
    public function isRunning()
    {
        $s = new DateTime($this->start_datetime);
        $e = new DateTime($this->end_datetime);

        $now = new DateTime();

        return $now >= $s && $now <= $e;
    }

    /**
     * Checks the offset till the start date.
     */
    public function getOffsetDays()
    {
        $s = new DateTime($this->start_datetime);
        return $s->diff(new DateTime)->days;
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
        return [
            'title' => $this->title,
            'description' => $this->description,
        ];
    }

    /**
     * @inheritdoc
     */
    public function cutTime()
    {
        $this->start_datetime = preg_replace('/\d{2}:\d{2}:\d{2}$/', '', $this->start_datetime);
        $this->end_datetime = preg_replace('/\d{2}:\d{2}:\d{2}$/', '', $this->end_datetime);
        $this->start_time = '00:00';
        $this->end_time = '23:59';
    }

    /**
     * Returns all entries filtered by the given $includes and $filters within a given range.
     * Note this function uses an open range which will include all events which start and/or end within the given search interval.
     *
     * @param DateTime $start
     * @param DateTime $end
     * @param array $includes
     * @param array $filters
     * @param int $limit
     * @return CalendarEntry[]
     * @throws Exception
     * @see CalendarEntryQuery
     */
    public static function getEntriesByRange(DateTime $start, DateTime $end, $includes = [], $filters = [], $limit = 50)
    {
        // Limit Range to one month
        $interval = $start->diff($end);
        if ($interval->days > 50) {
            throw new Exception('Range maximum exceeded!');
        }

        return CalendarEntryQuery::find()
            ->from($start)->to($end)
            ->filter($filters)
            ->userRelated($includes)
            ->limit($limit)->all();
    }

    /**
     * Returns all entries filtered by the given $includes and $filters within a given range.
     * Note this function uses an open range which will include all events which start and/or end within the given search interval.
     *
     * @param DateTime $start
     * @param DateTime $end
     * @param ContentContainerActiveRecord $container
     * @param array $filters
     * @param int $limit
     * @return CalendarEntry[]
     * @throws Exception
     * @see CalendarEntryQuery
     */
    public static function getContainerEntriesByRange(DateTime $start, DateTime $end, ContentContainerActiveRecord $container, $filters = [], $limit = 50)
    {
        // Limit Range to one month
        $interval = $start->diff($end);
        if ($interval->days > 50) {
            throw new Exception('Range maximum exceeded!');
        }

        return CalendarEntryQuery::find()
            ->container($container)
            ->from($start)->to($end)
            ->filter($filters)
            ->limit($limit)->all();
    }

    /**
     * Returns a list of upcoming events for the given $contentContainer.
     *
     * @param ContentContainerActiveRecord|null $contentContainer
     * @param int $daysInFuture
     * @param int $limit
     * @return CalendarEntry[]
     */
    public static function getUpcomingEntries(ContentContainerActiveRecord $contentContainer = null, $daysInFuture = 7, $limit = 5)
    {
        if ($contentContainer) {
            return CalendarEntryQuery::find()->container($contentContainer)->days($daysInFuture)->limit($limit)->all();
        } else {
            return CalendarEntryQuery::find()->userRelated()->days($daysInFuture)->limit($limit)->all();
        }
    }

}
