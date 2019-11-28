<?php


namespace humhub\modules\calendar\models\forms;


use Yii;
use Throwable;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\user\models\User;
use yii\base\Model;
use humhub\modules\calendar\interfaces\CalendarEventReminderIF;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\db\StaleObjectException;

class ReminderSettings extends Model
{
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
    public $user;

    /**
     * @var CalendarReminder[]
     */
    public $reminders;

    /**
     * @var
     */
    private $isDefaultsLoaded;

    /**
     * @var
     */
    private $hasDefaults;

    /**
     * @var
     */
    public $useDefaults;

    public function init()
    {
        parent::init();
        $this->initReminders();
        $this->initFlags();
    }

    protected function initReminders()
    {
        $this->reminders = $this->loadReminder();
        $this->reminders[] = new CalendarReminder();
    }

    protected function loadReminder($defaults = true)
    {
        if($this->entry) {
            return CalendarReminder::getEntryLevelReminder($this->entry, $this->user, $defaults);
        }

        return CalendarReminder::getDefaults($this->container, $defaults);
    }

    private function initFlags()
    {
        $this->isDefaultsLoaded = null;
        $this->hasDefaults = null;
        $this->isDefaultsLoaded();
        $this->hasDefaults();
        $this->useDefaults = $this->hasDefaults && $this->isDefaultsLoaded();
    }

    public function rules()
    {
        return [
            ['useDefaults', 'integer']
        ];
    }

    public function attributeLabels()
    {
        return [
            'useDefaults' => Yii::t('CalendarModule.reminder', 'Use default reminder')
        ];
    }

    /**
     * @return array
     */
    public static function getUnitSelection()
    {
        return [
            CalendarReminder::UNIT_HOUR => Yii::t('CalendarModule.reminder', 'Hour'),
            CalendarReminder::UNIT_DAY => Yii::t('CalendarModule.reminder', 'Day'),
            CalendarReminder::UNIT_WEEK => Yii::t('CalendarModule.reminder', 'Week'),
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
        // Keep this position, we need useDefaults after this line
        $parentLoad = parent::load($data, $formName);

        $reminderLoaded = false;

        if($parentLoad && $this->useDefaults) {
            $this->reminders = [];
        } else if(isset($data[CalendarReminder::instance()->formName()])) {
            $this->reminders = [];
            $reminderLoaded = true;
            $maxReminders = $this->getMaxReminders();
            foreach ($data[CalendarReminder::instance()->formName()] as $reminderData) {
                $reminder = $this->initReminder();
                $reminder->load($reminderData, '');
                $this->reminders[] = $reminder;

                if(count($this->reminders) >= $maxReminders) {
                    break;
                }
            }
        }

        return $reminderLoaded || $parentLoad;
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
        // Delete all reminder which do not match a submitted one
        $preservedReminders = $this->reset(true);

        $result = [];
        foreach ($this->reminders as $newReminder) {
            if($this->entry) {
                $newReminder->content_id = $this->entry->getContentRecord()->id;
            }

            $newReminder = $this->findReminder($newReminder, $preservedReminders) ?: $newReminder;

            if(!empty($newReminder->value)) {
                $newReminder->save();
                $result[] = $newReminder;
            }
        }

        $this->reminders = $result;

        // Check for disabled reminder if not global settings
        if(empty($this->reminders) && !$this->useDefaults && !$this->isGlobalSettings()) {
            $this->initReminder(1)->save();
        }

        $this->reminders[] = new CalendarReminder();

        if($this->isGlobalSettings() || $this->isContainerLevelSettings()) {
            CalendarReminder::flushDefautlts();
        }

        $this->initFlags();
        return true;
    }

    public function findReminder(CalendarReminder $reminder, $reminders)
    {
        foreach ($reminders as $existingReminder) {
            if($existingReminder->compare($reminder)) {
                return $existingReminder;
            }
        }

        return false;
    }

    public function getResetButtonUrl()
    {
        return Url::toReminderSettingsReset($this);
    }

    public function isGlobal()
    {
        return !$this->entry && $this->container === null;
    }

    public function isGlobalSettings()
    {
        return !$this->container && !$this->entry;
    }

    public function initReminder($disabled = false)
    {
       if($this->entry) {
           $result = $disabled
               ? CalendarReminder::initDisableEntryLevelDefaults($this->entry, $this->user)
               : CalendarReminder::initEntryLevel(null, null, $this->entry, $this->user);
       } else if($this->container) {
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
        if($this->isDefaultsLoaded !== null) {
            return $this->isDefaultsLoaded;
        }

        if(empty($this->reminders) || $this->isGlobalSettings()) {
            return $this->isDefaultsLoaded = false;
        }

        if($this->reminders[0]->isNewRecord) {
            return $this->isDefaultsLoaded = false;
        }

        if($this->isContainerLevelSettings()) {
            return $this->isDefaultsLoaded = !$this->reminders[0]->isContainerLevelReminder();
        }

        if($this->isUserLevelEntrySettings()) {
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
        if($this->hasDefaults !== null) {
            return $this->hasDefaults;
        }


        if($this->isGlobalSettings()) {
            return  $this->hasDefaults = false;
        }

        if($this->isEntryLevelSettings()) {
            return $this->hasDefaults = !empty(CalendarReminder::getEntryLevelDefaults($this->entry, $this->user));
        }

        if($this->isContainerLevelSettings()) {
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
            if(!$preserve || !$this->findReminder($oldReminder, $this->reminders)) {
                $oldReminder->delete();
            } else {
                $preservedReminders[] = $oldReminder;
            }
        }

        return $preservedReminders;
    }
}