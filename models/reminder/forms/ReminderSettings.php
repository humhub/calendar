<?php

namespace humhub\modules\calendar\models\reminder\forms;

use Yii;
use Throwable;
use humhub\modules\user\models\User;
use yii\base\Model;
use humhub\modules\calendar\interfaces\reminder\CalendarEventReminderIF;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\db\StaleObjectException;

class ReminderSettings extends Model
{
    public const REMINDER_TYPE_NONE = 0;
    public const REMINDER_TYPE_DEFAULT = 1;
    public const REMINDER_TYPE_CUSTOM = 2;

    /**
     * @var ContentContainerActiveRecord
     */
    public $container;

    /**
     * @var CalendarEventReminderIF
     */
    public $entry;

    /**
     * @var User
     */
    public $user = false;

    /**
     * @var CalendarReminder[]
     */
    public $reminders;

    /**
     * @var bool whether or not the defaults are currently loaded
     */
    private $isDefaultsLoaded;

    /**
     * @var bool whether or not there are global or space level defaults available
     */
    private $hasDefaults;

    /**
     * @var int
     */
    public $reminderType;

    public function init()
    {
        parent::init();
        $this->initReminders();
        $this->initFlags();
    }

    protected function initReminders()
    {
        $this->reminders = $this->loadReminder();
        if (count($this->reminders) < $this->getMaxReminders()) {
            $this->reminders[] = new CalendarReminder();
        }
    }

    protected function loadReminder($defaults = true)
    {
        if ($this->entry) {
            return CalendarReminder::getEntryLevelReminder($this->entry, $this->user, $defaults);
        }

        return CalendarReminder::getDefaults($this->container, $defaults);
    }

    public function getDefaults()
    {
        if ($this->isDefaultsLoaded) {
            return $this->reminders;
        }

        if ($this->entry) {
            return CalendarReminder::getEntryLevelDefaults($this->entry, $this->user);
        }

        return CalendarReminder::getDefaults();
    }

    private function initFlags()
    {
        $this->isDefaultsLoaded = null;
        $this->hasDefaults = null;
        $this->isDefaultsLoaded();
        $this->hasDefaults();

        if ($this->hasDefaults && $this->isDefaultsLoaded()) {
            $this->reminderType = static::REMINDER_TYPE_DEFAULT;
        } elseif ($this->isDisabled()) {
            $this->reminderType = static::REMINDER_TYPE_NONE;
        } else {
            $this->reminderType = static::REMINDER_TYPE_CUSTOM;
        }
    }

    private function isDisabled()
    {
        if (count($this->reminders) === 1 && $this->reminders[0]->isNewRecord) {
            return true;
        }

        // check for explicitly disabled reminders
        foreach ($this->reminders as $reminder) {
            if ($reminder->disabled) {
                return true;
            }
        }
        return false;
    }

    public function rules()
    {
        return [
            ['reminderType', 'integer'],
        ];
    }

    /**
     * @return array
     */
    public static function getUnitSelection()
    {
        return [
            CalendarReminder::UNIT_MINUTE => Yii::t('CalendarModule.base', 'Minute'),
            CalendarReminder::UNIT_HOUR => Yii::t('CalendarModule.base', 'Hour'),
            CalendarReminder::UNIT_DAY => Yii::t('CalendarModule.base', 'Day'),
            CalendarReminder::UNIT_WEEK => Yii::t('CalendarModule.base', 'Week'),
        ];
    }

    /**
     * @param array $data
     * @param null $formName
     * @return bool
     * @throws Throwable
     */
    public function load($data, $formName = null)
    {
        // Keep this position, we need reminderType after this line
        $parentLoad = parent::load($data, $formName);

        $reminderLoaded = false;

        if (!$parentLoad) {
            return false;
        }

        if ($this->isReminderType(static::REMINDER_TYPE_DEFAULT) || $this->isReminderType(static::REMINDER_TYPE_NONE)) {
            $this->reminders = [];
        } elseif (isset($data[CalendarReminder::instance()->formName()])) {
            $this->reminders = [];
            $reminderLoaded = true;
            $maxReminders = $this->getMaxReminders();
            foreach ($data[CalendarReminder::instance()->formName()] as $reminderData) {
                $reminder = $this->initReminder();
                $reminder->load($reminderData, '');
                $reminder->ensureValidValue();
                $this->reminders[] = $reminder;

                if (count($this->reminders) >= $maxReminders) {
                    break;
                }
            }
        }

        return $reminderLoaded || $parentLoad;
    }

    private function translate(CalendarReminder $reminder)
    {
        if ($reminder->unit === CalendarReminder::UNIT_DAY) {

        }
    }

    public function isReminderTypeUseDefault()
    {
        return $this->isReminderType(static::REMINDER_TYPE_DEFAULT);
    }

    public function isReminderType($type)
    {
        return $type == $this->reminderType;
    }

    /**
     * @return int
     */
    public function getMaxReminders()
    {
        return Yii::$app->getModule('calendar')->maxReminder;
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        // Delete all reminder which do not match a submitted one
        $preservedReminders = $this->reset(true);

        $result = [];
        foreach ($this->reminders as $newReminder) {
            if ($this->entry) {
                $newReminder->content_id = $this->entry->getContentRecord()->id;
            }

            $newReminder = $this->findReminder($newReminder, $preservedReminders) ?: $newReminder;

            if (!empty($newReminder->value)) {
                $newReminder->save();
                $result[] = $newReminder;
            }
        }

        $this->reminders = $result;

        // Check for disabled reminder if not global settings
        if (empty($this->reminders) && $this->hasDefaults && !$this->isReminderTypeUseDefault() && !$this->isGlobalSettings()) {
            $this->initReminder(1)->save();
        }

        if (count($this->reminders) < $this->getMaxReminders()) {
            $this->reminders[] = new CalendarReminder();
        }

        if ($this->isGlobalSettings() || $this->isContainerLevelSettings()) {
            CalendarReminder::flushDefautlts();
        }

        $this->initFlags();
        return true;
    }

    /**
     * @param CalendarReminder $reminder
     * @param CalendarReminder[] $reminders
     * @return bool
     */
    public function findReminder(CalendarReminder $reminder, $reminders)
    {
        foreach ($reminders as $existingReminder) {
            if ($existingReminder->compare($reminder)) {
                return $existingReminder;
            }
        }

        return false;
    }

    public function isGlobalSettings()
    {
        return !$this->container && !$this->entry;
    }

    public function initReminder($disabled = false)
    {
        if ($this->entry) {
            $result = $disabled
                ? CalendarReminder::initDisableEntryLevelDefaults($this->entry, $this->user)
                : CalendarReminder::initEntryLevel(null, null, $this->entry, $this->user);
        } elseif ($this->container) {
            $result = $disabled
                ? CalendarReminder::initDisableContainerDefaults($this->container)
                : CalendarReminder::initContainerDefault(null, null, $this->container);
        } else {
            // There is no disabled global reminder
            $result = CalendarReminder::initGlobalDefault(null, null);
        }

        return $result;
    }

    public function isDefaultsLoaded()
    {
        if ($this->isDefaultsLoaded !== null) {
            return $this->isDefaultsLoaded;
        }

        if (empty($this->reminders) || $this->isGlobalSettings()) {
            return $this->isDefaultsLoaded = false;
        }

        if ($this->reminders[0]->isNewRecord) {
            return $this->isDefaultsLoaded = false;
        }

        if ($this->isContainerLevelSettings()) {
            return $this->isDefaultsLoaded = !$this->reminders[0]->isContainerLevelReminder();
        }

        if ($this->isUserLevelEntrySettings()) {
            return $this->isDefaultsLoaded = !$this->reminders[0]->isUserLevelReminder();
        }

        // This is an entry level reminder, so the first loaded must be an entry level reminder to, otherwise global default was loaded
        return $this->isDefaultsLoaded = $this->reminders[0]->isDefaultReminder();
    }

    public function isContainerLevelSettings()
    {
        return $this->container !== null;
    }

    public function isUserLevelEntrySettings()
    {
        return $this->entry && $this->user;
    }

    public function isEntryLevelSettings()
    {
        return $this->entry !== null;
    }

    public function hasDefaults()
    {
        if ($this->hasDefaults !== null) {
            return $this->hasDefaults;
        }


        if ($this->isGlobalSettings()) {
            return  $this->hasDefaults = false;
        }

        if ($this->isEntryLevelSettings()) {
            return $this->hasDefaults = !empty(CalendarReminder::getEntryLevelDefaults($this->entry, $this->user));
        }

        if ($this->isContainerLevelSettings()) {
            return $this->hasDefaults = !empty(CalendarReminder::getDefaults());
        }

        return $this->hasDefaults = false;
    }

    /**
     * Deletes old reminders, if $preserve flag is set to true, this function will only delete old reminders,
     * which are not present in the current $reminders array.
     *
     * @return CalendarReminder[]
     * @throws StaleObjectException
     * @throws Throwable
     */
    public function reset($preserve = false)
    {
        // Load actual reminders without default fallback
        $oldReminders = $this->loadReminder(false);

        // Delete old reminders not existing in newReminders
        $preservedReminders = [];
        foreach ($oldReminders as $oldReminder) {
            if (!$preserve || !$this->findReminder($oldReminder, $this->reminders)) {
                $oldReminder->delete();
            } else {
                $preservedReminders[] = $oldReminder;
            }
        }

        return $preservedReminders;
    }

    /**
     * Delete all reminders of the calendar entry
     *
     * @return bool
     * @throws StaleObjectException
     */
    public function clear(): bool
    {
        if (!$this->entry) {
            return true;
        }

        /* @var CalendarReminder[] $reminders */
        $reminders = $this->loadReminder(false);

        foreach ($reminders as $reminder) {
            if (!$reminder->delete()) {
                return false;
            }
        }

        return true;
    }

    public function getReminderTypeOptions()
    {
        $result = [static::REMINDER_TYPE_NONE => Yii::t('CalendarModule.base', 'No reminder')];

        if ($this->hasDefaults) {
            $result[static::REMINDER_TYPE_DEFAULT] =  Yii::t('CalendarModule.base', 'Use default reminder');
        }

        $result[static::REMINDER_TYPE_CUSTOM] =  Yii::t('CalendarModule.base', 'Custom reminder');

        return $result;
    }
}
