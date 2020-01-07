<?php

namespace humhub\modules\calendar\models;

use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\AbstractRecurrentEvent;
use humhub\modules\calendar\interfaces\CalendarEventParticipationIF;
use humhub\modules\calendar\interfaces\CalendarEventReminderIF;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\user\components\ActiveQueryUser;
use Yii;
use yii\base\Exception;
use DateTime;
use DateTimeZone;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\CalendarEventStatusIF;
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
use Sabre\VObject\UUIDUtil;
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
 * @property CalendarEntryParticipant[] participantEntries
 * @property string $time_zone The timeZone this entry was saved, note the dates itself are always saved in app timeZone
 */
class CalendarEntry extends AbstractRecurrentEvent implements Searchable, CalendarEventStatusIF, CalendarEventReminderIF, CalendarEventParticipationIF
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

        if(!$this->time_zone) {
            $this->time_zone = CalendarUtils::getUserTimeZone(true);
        }

        $this->isAllDay(); // initialize all day

        $this->participation = new CalendarEntryParticipation(['entry' => $this]);
        $this->formatter = new CalendarDateFormatter(['calendarItem' => $this]);
    }

    public function setDefaults()
    {
        // Note we can't call this in `init()` because of https://github.com/humhub/humhub/issues/3734
        $this->participation->setDefautls();
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

        if($this->closed) {
            $labels[] = Label::danger(Yii::t('CalendarModule.base', 'canceled'))->sortOrder(15);
        }

        $type = $this->getType();
        if($type) {
            $labels[] = Label::asColor($type->color, $type->name)->sortOrder(310);
        }

        return parent::getLabels($labels);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $dateFormat =  'php:'.CalendarUtils::DB_DATE_FORMAT;

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
        // Check for legacy event, prior to v2.0 all_day events were translated to system timezone
        if($this->isAllDay() && !CalendarUtils::isAllDay($this->start_datetime, $this->end_datetime)) {
            $startDT = CalendarUtils::translateTimezone($this->start_datetime, CalendarUtils::getSystemTimeZone(), $this->time_zone, false);
            $startDT->setTime(0,0);
            $endDT =  CalendarUtils::translateTimezone($this->end_datetime, CalendarUtils::getSystemTimeZone(), $this->time_zone, false);
            $endDT->setTime(23,59, 59);
            $this->updateAttributes([
                'start_datetime' => CalendarUtils::toDBDateFormat($startDT),
                'end_datetime' => CalendarUtils::toDBDateFormat($endDT)
            ]);
        }
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

        $isAllDayMomentAfter = CalendarUtils::isAllDay($start, $end, false);
        $isAllDayNonStrict = CalendarUtils::isAllDay($start, $end, false);

        // Make sure all_day is set right
        if(!$this->all_day && $isAllDayNonStrict) {
            $this->all_day = 1;
        }

        // Make sure end and start time is set right for all_day events
        if($this->all_day && !$isAllDayMomentAfter) {
            $this->start_datetime = CalendarUtils::toDBDateFormat($start->setTime(0,0));
            $this->end_datetime = CalendarUtils::toDBDateFormat($end->setTime(23,59, 59));
        }

        if(empty($this->uid)) {
            $this->uid = CalendarUtils::generateUUid();
        }

        if($this->participation_mode === null) {
            $this->participation->setDefautls();
        }

        return parent::beforeSave($insert);
    }

    public static function createUUid($type = 'event')
    {
        return 'humhub-'.$type.'-' . UUIDUtil::getUUID();
    }

    public function beforeDelete()
    {
        $this->participation->deleteAll();

        if($this->isRecurringRoot()) {
            foreach($this->recurrenceInstances as $recurrence) {
                $recurrence->delete();
            }
        } elseif($this->isRecurringInstance()) {
            $root = $this->getRecurrenceRoot();
            if($root) {
                $root->setExdate(RecurrenceHelper::addExdates($root, $this));
                $root->saveRecurrenceInstance();
            }
        }

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
        $this->save();

        if($this->closed) {
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
    public function getType()
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
        $type = ($type instanceof ContentTag) ? $type : ContentTag::findOne(['id' => $type]);
        if($type->is(CalendarEntryType::class)) {
            CalendarEntryType::deleteContentRelations($this->content);
            $this->content->addTag($type);
        }
    }

    /**
     * @return ActiveQueryUser
     */
    public function findUsersByInterest()
    {
        if($this->content->container instanceof Space) {
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

                    return  Membership::getSpaceMembersQuery($this->content->container)
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
        return new DateTime($this->start_datetime, CalendarUtils::getSystemTimeZone(false));
    }

    /**
     * @return DateTime|\DateTimeInterface
     * @throws \Exception
     */
    public function getEndDateTime()
    {
        return new DateTime($this->end_datetime, CalendarUtils::getSystemTimeZone(false));
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
        if($this->all_day === null) {
            $this->all_day = 1;
        }

        return (boolean) $this->all_day;
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
        if($this->participation->isEnabled()) {
            $status = $this->getParticipationStatus(Yii::$app->user->identity);
            switch($status) {
                case CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED:
                    return Label::success(Yii::t('CalendarModule.base', 'Attending'))->right();
                case CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE:
                    if($this->allow_maybe) {
                        return Label::success(Yii::t('CalendarModule.base', 'Interested'))->right();
                    }
            }
        }

        return null;
    }

    public function generateIcs()
    {
        $timezone = Yii::$app->settings->get('timeZone');
        $ics = new ICS($this->title, $this->description,$this->start_datetime, $this->end_datetime, null, null, $timezone, $this->all_day);
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
     * Access url of the source content or other view
     *
     * @return string the timezone this item was originally saved, note this is
     */
    public function getUpdateUrl()
    {
        if($this->isRecurringInstance()) {
            return null;
        }

        return Url::toEditEntryAjax($this);
    }

    /**
     * Check if this calendar entry is editable, for example by checking `$this->content->isEditable()`.
     *
     * @return bool true if this entry is editable, false
     */
    public function isEditable()
    {
        return !$this->isRecurring() && $this->content->canEdit();
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
        if($this->closed) {
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
        return static::VIEW_MODE_MODAL;
    }

    /**
     * @param $event static
     * @return mixed
     */
    public function syncFromRecurrentRoot($event)
    {
        parent::syncFromRecurrentRoot($event);
        $this->title = $event->title;
        $this->description = $event->description;
        $this->color = $event->color;
        $this->time_zone = $event->time_zone;
        $this->participant_info = $event->participant_info;
        $this->participation_mode = $event->participation_mode;
        $this->all_day = $event->all_day;
        $this->allow_decline = $event->allow_decline;
        $this->allow_maybe = $event->allow_maybe;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRecurrenceViewUrl($cal = false)
    {
        return '';
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
        return $this->participation->getExternalParticipants( $status);
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
}
