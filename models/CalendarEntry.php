<?php

namespace humhub\modules\calendar\models;

use humhub\modules\calendar\CalendarUtils;
use humhub\modules\calendar\notifications\CanceledEvent;
use humhub\modules\calendar\notifications\ReopenedEvent;
use humhub\modules\calendar\permissions\ManageEntry;
use humhub\modules\calendar\widgets\EntryParticipants;
use humhub\modules\calendar\widgets\WallEntry;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentTag;
use humhub\modules\search\interfaces\Searchable;
use humhub\widgets\Label;
use Yii;
use DateTime;
use DateInterval;
use yii\base\Exception;
use humhub\libs\DbDateValidator;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\user\models\User;
use yii\db\ActiveQuery;

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
 * @property integer $allow_decline
 * @property integer $allow_maybe
 * @property string $participant_info
 * @property integer closed
 * @property integer max_participants
 * @property string $time_zone The timeZone this entry was saved, note the dates itself are always saved in app timeZone
 */
class CalendarEntry extends ContentActiveRecord implements Searchable
{
    /**
     * @inheritdoc
     */
    public $wallEntryClass = WallEntry::class;

    /**
     * Flag for Entry Form to set this content to public
     */
    public $is_public = Content::VISIBILITY_PUBLIC;

    /**
     * This attributes are used for time input
     */
    public $selected_participants = '';

    /**
     * @inheritdoc
     */
    public $managePermission = ManageEntry::class;

    /**
     * Participation Modes
     */
    const PARTICIPATION_MODE_NONE = 0;
    const PARTICIPATION_MODE_INVITE = 1;
    const PARTICIPATION_MODE_ALL = 2;

    /**
     * @var array all given participation modes as array
     */
    public static $participationModes = [
        self::PARTICIPATION_MODE_NONE,
        self::PARTICIPATION_MODE_INVITE,
        self::PARTICIPATION_MODE_ALL
    ];

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

        // Default participiation Mode
        $this->participation_mode = self::PARTICIPATION_MODE_ALL;
        $this->allow_maybe = 1;
        $this->allow_decline = 1;
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
            ['color', 'string'],
            [['start_datetime'], DbDateValidator::className()],
            [['end_datetime'], DbDateValidator::className()],
            [['all_day', 'allow_decline', 'allow_maybe', 'max_participants'], 'integer'],
            [['title'], 'string', 'max' => 200],
            [['participation_mode'], 'in', 'range' => self::$participationModes],
            [['end_datetime'], 'validateEndTime'],
            [['description', 'participant_info'], 'safe'],
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
            'type_id' => Yii::t('CalendarModule.base', 'Event Type'),
            'description' => Yii::t('CalendarModule.base', 'Description'),
            'all_day' => Yii::t('CalendarModule.base', 'All Day'),
            'allow_decline' => Yii::t('CalendarModule.base', 'Allow participation state \'decline\''),
            'allow_maybe' => Yii::t('CalendarModule.base', 'Allow participation state \'maybe\''),
            'participation_mode' => Yii::t('CalendarModule.base', 'Participation Mode'),
            'max_participants' => Yii::t('CalendarModule.base', 'Maximum number of participants'),
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
        if (new DateTime($this->start_datetime) >= new DateTime($this->end_datetime)) {
            $this->addError($attribute, Yii::t('CalendarModule.base', 'End time must be after start time!'));
        }
    }

    public function beforeSave($insert)
    {
        // Check is a full day span
        if ($this->all_day == 0 && CalendarUtils::isFullDaySpan(new DateTime($this->start_datetime), new DateTime($this->end_datetime))) {
            $this->all_day = 1;
        }

        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        return;
    }

    public function beforeDelete()
    {
        foreach (CalendarEntryParticipant::findAll(['calendar_entry_id' => $this->id]) as $participant) {
            $participant->delete();
        }

        return parent::beforeDelete();
    }

    public function toggleClosed()
    {
        $this->closed = ($this->closed) ? 0 : 1;
        $this->save();

        $participants = $this->getParticipantUsersByState([
            CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE,
            CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED,
            CalendarEntryParticipant::PARTICIPATION_STATE_INVITED]);

        if ($this->closed) {
            CanceledEvent::instance()->from(Yii::$app->user->getIdentity())->about($this)->sendBulk($participants);
        } else {
            ReopenedEvent::instance()->from(Yii::$app->user->getIdentity())->about($this)->sendBulk($participants);
        }
    }

    /**
     * Returns the related CalendarEntryType relation if given.
     *
     * @return CalendarEntryType
     */
    public function getType()
    {
        return CalendarEntryType::findByContent($this->content)->one();
    }

    /**
     * Sets the clanedarentry type.
     * @param $type
     */
    public function setType($type)
    {
        $type = ($type instanceof ContentTag) ? $type : ContentTag::findOne($type);
        if ($type->is(CalendarEntryType::class)) {
            CalendarEntryType::deleteContentRelations($this->content);
            $this->content->addTag($type);
        }
    }

    /**
     * @return ActiveQuery
     */
    public function getParticipants()
    {
        return $this->hasMany(CalendarEntryParticipant::class, ['calendar_entry_id' => 'id']);
    }

    /**
     * Returns an ActiveQuery for all participant user models of this meeting.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParticipantUsers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])->via('participants');
    }

    public function getParticipantUsersByState($state)
    {
        if (is_int($state)) {
            $state = [$state];
        }

        return $this->hasMany(User::class, ['id' => 'user_id'])->via('participants', function ($query) use ($state) {
            /* @var $query ActiveQuery */
            $query->andWhere(['IN', 'calendar_entry_participant.participation_state', $state]);
        })->all();
    }

    public function isParticipationAllowed()
    {
        return $this->participation_mode != self::PARTICIPATION_MODE_NONE;
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

        if (!$user) {
            return;
        }

        return CalendarEntryParticipant::findOne(['user_id' => $user->id, 'calendar_entry_id' => $this->id]);
    }

    public function isParticipant(User $user = null)
    {
        $participant = $this->findParticipant($user);
        return !empty($participant) && $participant->showParticipantInfo();
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

        if (!Yii::$app->user->isGuest) {
            Yii::$app->formatter->timeZone = Yii::$app->user->getIdentity()->time_zone;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'editable' => $this->content->canEdit(),
            'backgroundColor' => $this->color,
            'allDay' => $this->all_day,
            'updateUrl' => $this->content->container->createUrl('/calendar/entry/edit-ajax', ['id' => $this->id]),
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
        if ($user == null && !Yii::$app->user->isGuest) {
            $user = Yii::$app->user->getIdentity();
        }

        if ($this->closed || Yii::$app->user->isGuest || !$this->checkMaxParticipants()) {
            return false;
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

    public function checkMaxParticipants()
    {
        return empty($this->max_participants)
            || ($this->getParticipantCount(CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED) < $this->max_participants);
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
        return Yii::t('CalendarModule.base', 'Event');
    }

    /**
     * @inheritdoc
     */
    public function getIcon()
    {
        return 'fa-calendar';
    }

    /**
     * @inheritdoc
     */
    public function getLabels($result = [], $includeContentName = true)
    {
        $labels = [];

        if ($this->closed) {
            $labels[] = Label::danger('closed')->sortOrder(15);
        }

        $type = $this->getType();
        if ($type) {
            $labels[] = Label::asColor($type->color, $type->name)->sortOrder(310);
        }

        return parent::getLabels($labels);
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

    public function getParticipantCount($state = null)
    {
        if ($state) {
            return $this->getParticipants()->where(['participation_state' => $state])->count();
        } else {
            return $this->getParticipants()->count();
        }
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
