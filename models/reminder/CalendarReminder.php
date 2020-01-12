<?php


namespace humhub\modules\calendar\models\reminder;


use DateTime;
use humhub\components\ActiveRecord;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\reminder\CalendarEventReminderIF;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\user\models\User;
use yii\db\ActiveQuery;
use yii\db\Expression;

/**
 * Class CalendarReminder
 *
 * Types of reminder:
 *
 * # Global Default Reminder (global)
 *
 * - unit
 * - value
 * - content_id: null
 * - contentcontainer_id: null
 * - active: 1
 * - disabled: 0
 *
 * # Container Default Reminder (container level)
 *
 * - unit
 * - value
 * - content_id: null
 * - contentcontainer_id: space container id
 * - active: 1
 * - disabled: 0
 *
 * # Space Level Exception for an entry (container wide entry level)
 *
 * - unit
 * - value
 * - content_id Model id
 * - contentcontainer_id: null   // Note this is required for easy seperation of space level and user level exceptions
 * - active: 1
 * - disabled: 0
 *
 * # User Level Model Exception (user wide entry level)
 *
 * - unit
 * - value
 * - content_id: Model id
 * - contentcontainer_id: User container id
 * - active: 1
 * - disabled: 0
 *
 *
 * The following cases are used to disable a reminder in order to ignore defaults
 *
 * # Disabled container level reminder
 *
 * - unit: null
 * - value: null
 * - content_id: null
 * - contentcontainer_id: Space/User container Id
 * - active: 1
 * - disabled: 1
 *
 * # Disabled container entry level reminder
 *
 * - unit: null
 * - value: null
 * - content_id: entry content id
 * - contentcontainer_id: Space/User container Id
 * - active: 1
 * - disabled: 1
 *
 * # Disabled user entry level reminder
 *
 * - unit: null
 * - value: null
 * - content_id: entry content id
 * - contentcontainer_id: User id
 * - active: 1
 *
 * @package humhub\modules\calendar\models
 *
 * @property integer $id
 * @property integer $value
 * @property integer $unit
 * @property string $content_id
 * @property integer $contentcontainer_id
 * @property integer $active
 * @property integer $disabled
 * @property-read Content $entryContent
 */
class CalendarReminder extends ActiveRecord
{
    const UNIT_HOUR = 1;
    const UNIT_DAY = 2;
    const UNIT_WEEK = 3;

    const MAX_VALUES = [
        self::UNIT_HOUR => 24,
        self::UNIT_DAY => 31,
        self::UNIT_WEEK => 4,
    ];

    /**
     * @var CalendarReminder[]
     */
    private static $globalDefaults;

    /**
     * @var array
     */
    private static $containerDefaults = [];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'calendar_reminder';
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->active === null) {
            $this->active = 1;
        }

        if($this->disabled === null) {
            $this->disabled = 0;
        }
    }

    public function rules()
    {
        $rules = [
            [['unit'], 'in', 'range' => [static::UNIT_HOUR, static::UNIT_DAY, static::UNIT_WEEK]],
            [['value'], 'number', 'min' => 1],
            [['value'], 'validateValue']
        ];

        if ($this->active && !$this->disabled) {
            $rules[] = [['unit', 'value'], 'required'];
        }

        return $rules;
    }

    public function validateValue($attribute, $params)
    {
        if(!$this->unit) {
            return;
        }

        $max = static::MAX_VALUES[$this->unit];
        if(!$this->validateMaxRange()) {
            $this->addError('value',"Only values from 1 to $max are allowed");
        }
    }

    private function validateMaxRange()
    {
        if(!$this->unit) {
            return true;
        }

        return ((int) $this->value) <= static::MAX_VALUES[$this->unit];
    }

    public function ensureValidValue()
    {
        if($this->validateMaxRange()) {
            return;
        }
        
        switch ($this->unit) {
            case static::UNIT_HOUR:
                $this->unit = static::UNIT_DAY;
                $this->value = round(((int) $this->value) / 24);
                break;
            case static::UNIT_DAY:
                $this->unit = static::UNIT_WEEK;
                $this->value = round(((int) $this->value) / 7);
                break;
            case static::UNIT_WEEK:
                $this->value = static::MAX_VALUES[static::UNIT_WEEK];
                break;
            default:
                return;
        }

        $this->ensureValidValue();
    }

    /**
     * @param $unit
     * @param $value
     * @return CalendarReminder
     */
    public static function initGlobalDefault($unit, $value)
    {
        return new static(['unit' => $unit, 'value' => $value, 'active' => 1, 'disabled' => 0]);
    }

    /**
     * @param $unit
     * @param $value
     * @param ContentContainerActiveRecord $container
     * @return CalendarReminder
     */
    public static function initContainerDefault($unit, $value, ContentContainerActiveRecord $container)
    {
        return new static([
            'unit' => $unit,
            'value' => $value,
            'contentcontainer_id' => $container->contentcontainer_id,
            'active' => 1,
            'disabled' => 0
        ]);
    }

    /**
     * Initializes an inactive reminder for the given container, this is used in order to ignore global defaults.
     *
     * @param ContentContainerActiveRecord $container
     * @return CalendarReminder
     */
    public static function initDisableContainerDefaults(ContentContainerActiveRecord $container)
    {
        $instance = static::initContainerDefault(null, null, $container);
        $instance->disabled = 1;
        return $instance;
    }

    public static function getMaxReminderDaysInFuture()
    {
        $hourlyReminder = static::getMaxReminder(static::UNIT_HOUR);
        $dailyReminder = static::getMaxReminder(static::UNIT_DAY);
        $weeklyReminder = static::getMaxReminder(static::UNIT_WEEK);

        $hour = $hourlyReminder ? ceil($hourlyReminder->value / 24) : 0;
        $day = $dailyReminder ? $dailyReminder->value : 0;
        $week = $weeklyReminder ? $weeklyReminder->value * 7 : 0;

        return max($hour, $day, $week);
    }

    /**
     * @param $unit
     * @return static
     */
    private static function getMaxReminder($unit)
    {
        return static::find()->where(['unit' => $unit])->andWhere(['active' => 1])->andWhere(['disabled' => 0])
            ->andWhere('value IS NOT NULL')->orderBy('value DESC')->one();
    }

    /**
     * @param $unit
     * @param $value
     * @param CalendarEventReminderIF $model
     * @param User|null $user
     * @return CalendarReminder
     */
    public static function initEntryLevel($unit, $value, CalendarEventReminderIF $model, $user = null)
    {
        $instance = new static(['unit' => $unit, 'value' => $value, 'active' => 1, 'content_id' => $model->getContentRecord()->id,  'disabled' => 0]);

        if($user) {
            $instance->contentcontainer_id = $user->contentcontainer_id;
        }

        return $instance;
    }

    /**
     * Initializes an inactive entry level reminder, this is used in order to ignore global and container defaults.
     *
     * @param ContentContainerActiveRecord $container
     * @return CalendarReminder
     */
    public static function initDisableEntryLevelDefaults(CalendarEventReminderIF $model, $user = null)
    {
        $instance = static::initEntryLevel(null, null, $model, $user);
        $instance->disabled = 1;
        return $instance;
    }

    /**
     * @return bool
     */
    public function isUserLevelReminder()
    {
        return $this->isEntryLevelReminder() && $this->contentcontainer_id !== null;
    }

    /**
     * @return bool
     */
    public function isContainerWideEntryLevelReminder()
    {
        return $this->isEntryLevelReminder() && $this->contentcontainer_id === null;
    }

    /**
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function afterDelete()
    {
        foreach (CalendarReminderSent::findByReminder($this)->all() as $reminderSent) {
            $reminderSent->delete();
        }

        parent::afterDelete(); // TODO: Change the autogenerated stub
    }

    /**
     * @return ActiveQuery
     */
    public function getEntryContent()
    {
        return $this->hasOne(Content::class, ['id' => 'content_id']);
    }

    /**
     * @return CalendarEventReminderIF
     * @throws \yii\db\IntegrityException
     */
    public function getEntry()
    {
        $content = $this->entryContent;
        if($content) {
            return CalendarUtils::getCalendarEvent($content);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isEntryLevelReminder()
    {
        return $this->content_id !== null;
    }

    /**
     * @return bool
     */
    public function isDefaultReminder()
    {
        return !$this->isEntryLevelReminder();
    }

    /**
     * @return bool
     */
    public function isContainerLevelReminder()
    {
        return !$this->isEntryLevelReminder() && $this->contentcontainer_id !== null;
    }

    /**
     * @return ContentContainer[]
     */
    public static function getContainerWithDefaultReminder()
    {
        $subQuery = static::find()
            ->where(['IS', 'content_id', new Expression('NULL')])
            ->andWhere('calendar_reminder.contentcontainer_id = contentcontainer.id');

        return ContentContainer::find()->where(['EXISTS',  $subQuery])->all();
    }

    /**
     * @param ContentContainerActiveRecord|null $container
     * @return static[]
     */
    public static function getDefaults(ContentContainerActiveRecord $container = null, $globalFallback = false)
    {
        $result = static::getDefaultFromCache($container, $globalFallback);

        if($result !== null) {
            return $result;
        }

        $query = static::find();

        if ($container) {
            $query->andWhere(['contentcontainer_id' => $container->contentcontainer_id]);
        } else {
            $query->andWhere(['IS', 'contentcontainer_id', new Expression('NULL')]);
        }

        $query->andWhere(['IS', 'content_id', new Expression('NULL')]);
        $query->orderBy('unit ASC, value ASC');

        $result = $query->all();

        static::setDefaultResult($container, $result);

        if ($container && empty($result) && $globalFallback) {
            $result = static::getDefaults();
        }

        return $result;
    }

    private static function setDefaultResult(ContentContainerActiveRecord $container = null, $result)
    {
        if($container) {
            static::$containerDefaults[$container->contentcontainer_id] = $result;
        } else {
            static::$globalDefaults = $result;
        }
    }

    public static function flushDefautlts()
    {
        static::$containerDefaults = [];
        static::$globalDefaults  = null;
    }

    private static function getDefaultFromCache(ContentContainerActiveRecord $container = null, $globalFallback = false)
    {
        if($container && !isset(static::$containerDefaults[$container->contentcontainer_id])) {
            return null; // No cached results
        }

        if($container) {
            $result = static::$containerDefaults[$container->contentcontainer_id];
            if(!empty($result) || !$globalFallback) {
                return $result;
            }
        }

        if(static::$globalDefaults !== null) {
            return static::$globalDefaults;
        }

        return null;
    }

    /**
     * @param bool $filterNotSent
     * @return ActiveQuery
     */
    public static function findEntryLevelReminder($active = true)
    {
        $query = static::find()->where(['IS NOT', 'content_id', new Expression('NULL')]);

        if($active) {
            $query->andWhere(['active' => 1]);
        }

        $query->orderBy('calendar_reminder.contentcontainer_id DESC, unit ASC, value ASC');

        return $query;
    }

    /**
     * Finds reminder by model, this does not include default reminders.
     *
     * This function returns the reminder in the following order:
     *
     *  - Sort User level reminder first, ordered by the container id of the user
     *  - Sort reminder close to the event first
     *
     * @param CalendarEventReminderIF $model
     * @return CalendarReminder[]
     */
    public static function getEntryLevelReminder(CalendarEventReminderIF $model, $user = true, $defaultFallback = false)
    {
        if($model->getContentRecord()->isNewRecord) {
            return $defaultFallback ? static::getEntryLevelDefaults($model, $user) : [];
        }

        $query = static::find()
            ->where(['content_id' => $model->getContentRecord()->id])
            // We want user entry level first with given contentcontainer_id, then sort by interval
            ->orderBy('calendar_reminder.contentcontainer_id DESC, disabled DESC, unit ASC, value ASC');

        if($user === false) {
            $query->andWhere(['IS' ,'contentcontainer_id', new Expression('NULL')]);
        } else if($user instanceof User) {
            $query->andWhere(['contentcontainer_id' => $user->contentcontainer_id]);
        } else {
            //$query->andWhere(['contentcontainer_id' => $model->getContentRecord()->contentcontainer_id]);
        }

        $result = $query->all();

        return empty($result) && $defaultFallback ? static::getEntryLevelDefaults($model, $user) : $result;
    }

    public static function getEntryLevelDefaults(CalendarEventReminderIF $model, $user = true)
    {
        $result = [];

        if($user instanceof User) {
            $result = static::getEntryLevelReminder($model, false, true);
        }

        if(empty($result)) {
            $result = static::getDefaults($model->getContentRecord()->container, true);
        }

        return $result;
    }

    /**
     * @param CalendarEventReminderIF $entry
     * @return bool
     */
    public function isActive(CalendarEventReminderIF $entry)
    {
        // Non default reminder are deactivated after first sent
        if (!$this->active || $this->disabled) {
            return false;
        }

        if($this->isDefaultReminder() && CalendarReminderSent::check($this, $entry)) {
            return false;
        }

        return true;
    }

    /**
     * Checks the due date of the reminder message.
     * @param CalendarEventReminderIF $model
     * @return bool
     * @throws \Exception
     */
    public function checkMaturity(CalendarEventReminderIF $model)
    {
        if(!$this->active || $this->isDisabled()) {
            return false;
        }

        $sendDate = $model->getStartDateTime()->modify($this->getModify());
        return $sendDate <= new DateTime();
    }

    public function isDisabled()
    {
        return $this->disabled;
    }

    private function getModify()
    {
        if(!$this->unit || ! $this->value) {
            return '-0 hours';
        }

        switch ($this->unit) {
            case static::UNIT_HOUR:
                $modifyUnit = 'hours';
                break;
            case static::UNIT_DAY:
                $modifyUnit = 'days';
                break;
            case static::UNIT_WEEK:
                $modifyUnit = 'weeks';
                break;
            default:
                $modifyUnit = 'hours';
        }

        // add tolerance for cron delay....
        return '-'.$this->value.' '.$modifyUnit;
    }

    /**
     * @param CalendarEventReminderIF $entry
     */
    public function acknowledge(CalendarEventReminderIF $entry)
    {
        if($this->isEntryLevelReminder()) {
            $this->updateAttributes(['active' => 0]);
        }

        CalendarReminderSent::create($this, $entry);
    }

    public function compare(CalendarReminder $reminder)
    {
        return $this->unit == $reminder->unit
            && $this->value == $reminder->value
            && $this->contentcontainer_id == $reminder->contentcontainer_id
            && $this->content_id == $reminder->content_id;
    }
}