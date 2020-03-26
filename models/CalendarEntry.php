<?php

namespace humhub\modules\calendar\models;

use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\event\CalendarEntryTypeSetting;
use humhub\modules\calendar\interfaces\fullcalendar\FullCalendarEventIF;
use humhub\modules\calendar\interfaces\participation\CalendarEventParticipationIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use humhub\modules\calendar\interfaces\reminder\CalendarEventReminderIF;
use humhub\modules\calendar\interfaces\recurrence\AbstractRecurrenceQuery;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\calendar\models\recurrence\CalendarEntryRecurrenceQuery;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\user\components\ActiveQueryUser;
use Yii;
use DateTime;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\event\CalendarEventStatusIF;
use humhub\modules\calendar\notifications\CanceledEvent;
use humhub\modules\calendar\notifications\ReopenedEvent;
use humhub\modules\calendar\permissions\ManageEntry;
use humhub\modules\calendar\widgets\WallEntry;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentTag;
use humhub\modules\search\interfaces\Searchable;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\widgets\Label;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\user\models\User;

/**
 * This is the model class for table "calendar_entry".
 *
 * The followings are the available columns in table 'calendar_entry':
 * @property integer $id
 * @property string $title
 * @property string $description
 * @property string $start_datetime
 * @property string $end_datetime
 * @property integer $all_day
 * @property integer $participation_mode
 * @property string $color
 * @property string $uid
 * @property integer $allow_decline
 * @property integer $allow_maybe
 * @property string $participant_info
 * @property integer closed
 * @property integer max_participants
 * @property string rrule
 * @property string recurrence_id
 * @property int parent_event_id
 * @property string exdate
 * @property string sequence
 * @property CalendarEntryParticipant[] participantEntries
 * @property string $time_zone The timeZone this entry was saved, note the dates itself are always saved in app timeZone
 */
class CalendarEntry extends ContentActiveRecord implements Searchable, RecurrentEventIF, FullCalendarEventIF,
    CalendarEventStatusIF, CalendarEventReminderIF, CalendarEventParticipationIF
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
     * @inheritdoc
     */
    public $managePermission = ManageEntry::class;

    /**
     * @var CalendarDateFormatter
     */
    public $formatter;

    /**
     * @var array attached files
     */
    public $files = [];

    /**
     * @inheritdoc
     */
    public $canMove = true;

    /**
     * @var CalendarEntryRecurrenceQuery
     */
    private $query;

    /**
     * @inheritdoc
     */
    public $moduleId = 'calendar';

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
    const FILTER_NOT_RESPONDED = 3;
    const FILTER_RESPONDED = 4;
    const FILTER_MINE = 5;

    /**
     * @var CalendarEntryParticipation
     */
    public $participation;

    public function init()
    {
        parent::init();

        $this->query = new CalendarEntryRecurrenceQuery(['event' => $this]);

        if (!$this->time_zone) {
            $this->time_zone = CalendarUtils::getUserTimeZone(true);
        }

        if ($this->sequence === null) {
            $this->sequence = 0;
        }

        $this->isAllDay(); // initialize all day

        $this->participation = new CalendarEntryParticipation(['entry' => $this]);
        $this->formatter = new CalendarDateFormatter(['calendarItem' => $this]);
    }

    public function setDefaults()
    {
        // Note we can't call this in `init()` because of https://github.com/humhub/humhub/issues/3734
        $this->participation->setDefautls();

        if(!$this->color && $this->content->container) {
            $typeSetting = new CalendarEntryTypeSetting(['type' => $this->getEventType(), 'contentContainer' => $this->content->container]);
            $this->color = $typeSetting->getColor();
        }
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
            $labels[] = Label::danger(Yii::t('CalendarModule.base', 'canceled'))->sortOrder(15);
        }

        $type = $this->getEventType();
        if ($type) {
            $labels[] = Label::asColor($type->color, $type->name)->sortOrder(310);
        }

        return parent::getLabels($labels);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $dateFormat = 'php:' . CalendarUtils::DB_DATE_FORMAT;

        return [
            [['files'], 'safe'],
            [['title', 'start_datetime', 'end_datetime'], 'required'],
            ['color', 'string'],
            [['start_datetime'], 'date', 'format' => $dateFormat],
            [['end_datetime'], 'date', 'format' => $dateFormat],
            [['all_day', 'allow_decline', 'allow_maybe', 'max_participants'], 'integer'],
            [['title'], 'string', 'max' => 200],
            [['participation_mode'], 'in', 'range' => self::$participationModes],
            [['end_datetime'], 'validateEndTime'],
            [['description', 'participant_info'], 'safe'],
        ];
    }

    public function afterFind()
    {
        if (!$this->isAllDay()) {
            parent::afterFind();
            return;
        }

        /**
         * Here we translate legacy all day events as follows:
         *
         * Old all day events were translated into system timezone, now we ignore timezone translation for all day events
         * so we calculate back and ensure valid all day format.
         *
         * Old all day events ended right before with end time 23:59, now we save in moment after format.
         */
        if (!CalendarUtils::isAllDay($this->start_datetime, $this->end_datetime)) {
            $startDT = CalendarUtils::translateTimezone($this->start_datetime, CalendarUtils::getSystemTimeZone(), $this->time_zone, false);
            $startDT->setTime(0, 0);
            $endDT = CalendarUtils::translateTimezone($this->end_datetime, CalendarUtils::getSystemTimeZone(), $this->time_zone, false);
            $endDT->modify('+1 day')->setTime(0, 0, 0);
            $this->updateAttributes([
                'start_datetime' => CalendarUtils::toDBDateFormat($startDT),
                'end_datetime' => CalendarUtils::toDBDateFormat($endDT)
            ]);
        } else if (!CalendarUtils::isAllDay($this->start_datetime, $this->end_datetime, true)) {
            // Translate from 23:59 end dates to 00:00 end dates
            $start = $this->getStartDateTime();
            $end = $this->getEndDateTime()->modify('+1 hour');
            CalendarUtils::ensureAllDay($start, $end);
            $this->updateAttributes([
                'start_datetime' => CalendarUtils::toDBDateFormat($start),
                'end_datetime' => CalendarUtils::toDBDateFormat($end)
            ]);
        }


        parent::afterFind();
    }

    /**
     * Validator for the endtime field.
     * Execute this after DbDateValidator
     *
     * @param string $attribute attribute name
     * @param array $params parameters
     * @throws \Exception
     */
    public function validateEndTime($attribute, $params)
    {
        if (new DateTime($this->start_datetime) >= new DateTime($this->end_datetime)) {
            $this->addError($attribute, Yii::t('CalendarModule.base', "End time must be after start time!"));
        }
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
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
        ];
    }

    public function beforeSave($insert)
    {
        $start = new DateTime($this->start_datetime);
        $end = new DateTime($this->end_datetime);

        // Make sure end and start time is set right for all_day events
        if ($this->all_day) {
            CalendarUtils::ensureAllDay($start, $end);
            $this->start_datetime = CalendarUtils::toDBDateFormat($start);
            $this->end_datetime = CalendarUtils::toDBDateFormat($end);
        }

        if ($this->participation_mode === null) {
            $this->participation->setDefautls();
        }

        return parent::beforeSave($insert);
    }

    /**
     * @return mixed
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function beforeDelete()
    {
        $this->participation->deleteAll();
        return parent::beforeDelete();
    }

    public function setExdate($exdateStr)
    {
        $this->exdate = $exdateStr;
    }

    public function getRecurrenceInstances()
    {
        return $this->hasMany(CalendarEntry::class, ['parent_event_id' => 'id']);
    }

    /**
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     */
    public function toggleClosed()
    {
        $this->closed = $this->closed ? 0 : 1;
        $this->saveEvent();

        if ($this->closed) {
            $this->participation->sendUpdateNotification(CanceledEvent::class);
        } else {
            $this->participation->sendUpdateNotification(ReopenedEvent::class);
        }
    }

    /**
     * Returns the related CalendarEntryType relation if given.
     *
     * @return CalendarEntryType
     */
    public function getEventType()
    {
        $type = CalendarEntryType::findByContent($this->content)->one();

        return $type ? $type : new CalendarEntryType();
    }

    /**
     * Sets the clanedarentry type.
     * @param $type
     */
    public function setType($type)
    {
        if(empty($type)) {
            $this->removeType();
            return;
        }

        $type = ($type instanceof ContentTag) ? $type : ContentTag::findOne(['id' => $type]);
        if ($type->is(CalendarEntryType::class)) {
            $this->removeType();
            $this->content->addTag($type);
        }
    }

    /**
     * Removes the calendar entry type.
     * @param $type
     */
    public function removeType()
    {
        CalendarEntryType::deleteContentRelations($this->content);
    }

    /**
     * @return ActiveQueryUser
     */
    public function getReminderUserQuery()
    {
        if ($this->content->container instanceof Space) {
            switch ($this->participation_mode) {
                case static::PARTICIPATION_MODE_NONE:
                    return Membership::getSpaceMembersQuery($this->content->container);
                case static::PARTICIPATION_MODE_ALL:
                    $userDeclinedQuery = CalendarEntryParticipant::find()
                        ->where('calendar_entry_participant.user_id = user.id')
                        ->andWhere(['=', 'calendar_entry_participant.calendar_entry_id', $this->id])
                        ->andWhere(['IN', 'calendar_entry_participant.participation_state',
                            [CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED]]);

                    $participantQuery = CalendarEntryParticipant::find()
                        ->where('calendar_entry_participant.user_id = user.id')
                        ->andWhere(['=', 'calendar_entry_participant.calendar_entry_id', $this->id])
                        ->andWhere(['IN', 'calendar_entry_participant.participation_state',
                            [CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE, CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED]]);

                    return Membership::getSpaceMembersQuery($this->content->container)
                        ->andWhere(['NOT EXISTS', $userDeclinedQuery])
                        ->orWhere(['EXISTS', $participantQuery]);

                case static::PARTICIPATION_MODE_INVITE:
                    return $this->participation->findParticipants([
                        CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED,
                        CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE]);
            }
        } elseif ($this->content->container instanceof User) {
            switch ($this->participation_mode) {
                case static::PARTICIPATION_MODE_NONE:
                    return User::find()->where(['id' => $this->content->container->id]);
                case static::PARTICIPATION_MODE_INVITE:
                case static::PARTICIPATION_MODE_ALL:
                    // TODO: remind all friends who did not decline for MODE_ALL
                    return $this->participation->findParticipants([
                        CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED,
                        CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE])->orWhere(['user.id' => $this->content->container]);

            }
        }

        // Fallback should only happen for global events, which are not supported
        return User::find()->where(['id' => $this->content->createdBy->id]);
    }

    public function getUrl()
    {
        return Url::toEntry($this);
    }

    /**
     * Checks if the event is currently running.
     */
    public function isRunning()
    {
        return $this->formatter->isRunning();
    }

    /**
     * @inheritdoc
     */
    public function getTimezone()
    {
        return ($this->isAllDay()) ? 'UTC' : $this->time_zone;
    }

    /**
     * @return DateTime|\DateTimeInterface
     * @throws \Exception
     */
    public function getStartDateTime()
    {
        return new DateTime($this->start_datetime, CalendarUtils::getSystemTimeZone());
    }

    /**
     * @return DateTime|\DateTimeInterface
     * @throws \Exception
     */
    public function getEndDateTime()
    {
        return new DateTime($this->end_datetime, CalendarUtils::getSystemTimeZone());
    }

    /**
     * @param string $format
     * @return string
     */
    public function getFormattedTime($format = 'long')
    {
        return $this->formatter->getFormattedTime($format);
    }

    /**
     * @return boolean weather or not this item spans exactly over a whole day
     */
    public function isAllDay()
    {
        if ($this->all_day === null) {
            $this->all_day = 1;
        }

        return (boolean)$this->all_day;
    }

    /**
     * Access url of the source content or other view
     *
     * @return string the timezone this item was originally saved, note this is
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns a badge for the snippet
     *
     * @return string the timezone this item was originally saved, note this is
     * @throws \Throwable
     */
    public function getBadge()
    {
        if ($this->participation->isEnabled()) {
            $status = $this->getParticipationStatus(Yii::$app->user->identity);
            switch ($status) {
                case CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED:
                    return Label::success(Yii::t('CalendarModule.base', 'Attending'))->right();
                case CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE:
                    if ($this->allow_maybe) {
                        return Label::success(Yii::t('CalendarModule.base', 'Interested'))->right();
                    }
            }
        }

        return null;
    }

    public function generateIcs()
    {
        $timezone = Yii::$app->settings->get('timeZone');
        $ics = new ICS($this->title, $this->description, $this->start_datetime, $this->end_datetime, null, null, $timezone, $this->all_day);
        return $ics;
    }

    public function afterMove(ContentContainerActiveRecord $container = null)
    {
        $this->participation->afterMove($container);
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    public function getLocation()
    {
        return null;
    }

    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Check if this calendar entry is editable, for example by checking `$this->content->isEditable()`.
     *
     * @return bool true if this entry is editable, false
     */
    public function isUpdatable()
    {
        return !RecurrenceHelper::isRecurrent($this) && $this->content->canEdit();
    }

    /**
     * @return string hex color string e.g: '#44B5F6'
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @return mixed
     */
    public function getEventStatus()
    {
        if ($this->closed) {
            return CalendarEventStatusIF::STATUS_CANCELLED;
        }

        return CalendarEventStatusIF::STATUS_CONFIRMED;
    }

    /**
     * @return Content
     */
    public function getContentRecord()
    {
        return $this->content;
    }

    /**
     * Additional configuration options
     * @return array
     */
    public function getCalendarOptions()
    {
        return [];
    }

    /**
     * Access url of the source content or other view
     *
     * @return string the timezone this item was originally saved, note this is
     */
    public function getCalendarViewUrl()
    {
        return Url::toEntry($this, 1);
    }

    /**
     * @return string view mode 'modal', 'blank', 'redirect'
     */
    public function getCalendarViewMode()
    {
        if(empty($this->getCalendarViewUrl())) {
            return static::VIEW_MODE_REDIRECT;
        }
        
        return static::VIEW_MODE_MODAL;
    }

    /**
     * Returns the participation state for a given user or guests if $user is null.
     *
     * @param User $user
     * @return int
     */
    public function getParticipationStatus(User $user = null)
    {
        return $this->participation->getParticipationStatus($user);
    }

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function setParticipationStatus(User $user, $status = self::PARTICIPATION_STATUS_ACCEPTED)
    {
        $this->participation->setParticipationStatus($user, $status);
    }

    /**
     * @inheritDoc
     */
    public function getExternalParticipants($status = [])
    {
        return $this->participation->getExternalParticipants($status);
    }

    /**
     * @inheritDoc
     */
    public function getParticipants($status = [])
    {
        return $this->participation->getParticipants($status);
    }

    /**
     * @inheritDoc
     */
    public function getParticipantCount($status = [])
    {
        return $this->participation->getParticipantCount($status);
    }

    /**
     * CalendarEntryParticipant relation.
     *
     * Important: Do not remove, since its used in `via()` queries.
     */
    public function getParticipantEntries()
    {
        return $this->participation->getParticipantEntries();
    }

    /**
     * @param User|null $user
     * @return mixed
     * @throws \Throwable
     */
    public function canRespond(User $user = null)
    {
        return $this->participation->canRespond($user);
    }

    public function getId()
    {
        return $this->id;
    }


    public function getRecurrenceId()
    {
        return $this->recurrence_id;
    }

    public function setRecurrenceId($recurrenceId)
    {
        $this->recurrence_id = $recurrenceId;
    }

    public function setRecurrenceRootId($rootId)
    {
        $this->parent_event_id = $rootId;
    }

    public function getRrule()
    {
        return $this->rrule;
    }

    public function setRrule($rrule)
    {
        $this->rrule = $rrule;
    }

    public function getExdate()
    {
        return $this->exdate;
    }

    public function getRecurrenceRootId()
    {
        return $this->parent_event_id;
    }

    /**
     * @param $start
     * @param $end
     * @param $recurrenceId
     * @return Content|mixed
     * @throws \yii\base\Exception
     */
    public function createRecurrence($start, $end)
    {
        $instance = new self($this->content->container, $this->content->visibility);
        $instance->start_datetime = $start;
        $instance->end_datetime = $end;

        // Currently we do not support notifications and wall entries for recurring instances
        $instance->silentContentCreation = true;
        $instance->content->stream_channel = null;

        return $instance;
    }

    /**
     * @param static $root
     * @param static $original
     * @return mixed
     */
    public function syncEventData($root, $original = null)
    {
        $this->content->created_by = $root->content->created_by;
        $this->content->visibility = $root->content->visibility;

        if (!$original || empty($this->description) || $original->description === $this->description) {
            $this->description = $root->description;
        }

        if (!$original || empty($this->participant_info) || $original->participant_info === $this->participant_info) {
            $this->participant_info = $root->participant_info;
        }

        $this->title = $root->title;
        $this->color = $root->color;
        $this->time_zone = $root->time_zone;
        $this->participation_mode = $root->participation_mode;
        $this->all_day = $root->all_day;
        $this->allow_decline = $root->allow_decline;
        $this->allow_maybe = $root->allow_maybe;
    }

    /**
     * @return AbstractRecurrenceQuery
     */
    public function getRecurrenceQuery()
    {
        return $this->query;
    }

    public function getSequence()
    {
        return $this->sequence;
    }

    public function setSequence($sequence)
    {
        $this->sequence = $sequence;
    }

    /**
     * @param array $status
     * @return ActiveQueryUser
     */
    public function findParticipants($status = [])
    {
        return $this->participation->findParticipants($status);
    }

    /**
     * @return User
     */
    public function getOrganizer()
    {
        return $this->participation->getOrganizer();
    }

    /**
     * @return DateTime|null
     * @throws \Exception
     */
    public function getLastModified()
    {
        if (is_string($this->content->updated_at)) {
            return new DateTime($this->content->updated_at);
        }

        return null;
    }

    /**
     * Adds additional options supported by fullcalendar: https://fullcalendar.io/docs/event-object
     * @return array
     */
    public function getFullCalendarOptions()
    {
        return [];
    }

    /**
     * @return string
     * @deprecated since 1.0 use CalendarUtils::generateUUid()
     */
    public static function createUUid()
    {
        return CalendarUtils::generateUUid();
    }

    /**
     * @inheritDoc
     */
    public function updateTime(DateTime $start, DateTime $end)
    {
        $this->start_datetime = CalendarUtils::toDBDateFormat($start);
        $this->end_datetime = CalendarUtils::toDBDateFormat($end);
        return $this->save();
    }

    /**
     * The timezone string of the end date.
     * In case the start and end timezone is the same, this function can return null.
     *
     * @return string
     */
    public function getEndTimezone()
    {
        return null;
    }

    /**
     * Should update all data used by the event interface setter.
     *
     * @return bool|int
     */
    public function saveEvent()
    {
       return $this->save();
    }
}
