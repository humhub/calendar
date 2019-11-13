<?php


namespace humhub\modules\calendar\models\forms;


use humhub\modules\user\models\User;
use Throwable;
use Yii;
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
     * @var CalendarReminder[]
     */
    private $newReminders = [];

    public function init()
    {
        parent::init();
        $this->loadReminder();

    }

    public function loadReminder($defaults = true)
    {
        if(!$this->entry) {
            $this->reminders = CalendarReminder::getDefaults($this->container, $defaults);
        } else {
            $this->reminders = CalendarReminder::getEntryLevelReminder($this->entry, $this->user, $defaults);
        }

        $this->reminders[] = new CalendarReminder();
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
        $formName = $formName ?: CalendarReminder::instance()->formName();
        if(isset($data[$formName])) {
            $this->newReminders = [];
            foreach ($data[$formName] as $reminderData) {
                $reminder = $this->initReminder();
                $reminder->load($reminderData, '');
                $this->newReminders[] = $reminder;
            }
            return true;
        }

        return false;
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

    public function save()
    {
        // Load actual reminders without default fallback
        $this->loadReminder(false);

        // Delete old reminders not existing in newReminders
        $oldReminders = [];
        foreach ($this->reminders as $oldReminder)
        {
            if(!$this->findReminder($oldReminder, $this->newReminders)) {
                $oldReminder->delete();
            } else {
                $oldReminders[] = $oldReminder;
            }
        }

        // Reset reminders
        $this->reminders = [];
        foreach ($this->newReminders as $newReminder) {
            if($this->entry) {
                $newReminder->content_id = $this->entry->getContentRecord()->id;
            }

            $newReminder = $this->findReminder($newReminder, $oldReminders) ?: $newReminder;

            if(!empty($newReminder->value)) {
                $newReminder->save();
                $this->reminders[] = $newReminder;
            }
        }

        if(empty($this->reminders) && !$this->isGlobalSettings()) {
            $this->initReminder(0)->save();
        }

        $this->reminders[] = new CalendarReminder();
        return true;
    }

    private function isGlobalSettings()
    {
        return !$this->container && !$this->entry;
    }

    public function initReminder($active = true)
    {
       if($this->entry) {
           $result = CalendarReminder::initEntryLevel(null, null, $this->entry, $this->user);
       } else if($this->container) {
           $result = CalendarReminder::initContainerDefault(null, null, $this->container);
       } else {
           $result = CalendarReminder::initGlobalDefault(null, null);
       }

       if(!$active) {
           $result->active = 0;
       }

       return $result;
    }
}