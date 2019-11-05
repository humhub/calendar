<?php


namespace humhub\modules\calendar\models\forms;


use humhub\modules\user\models\User;
use Yii;
use yii\base\Model;
use humhub\modules\calendar\interfaces\Remindable;
use humhub\modules\calendar\models\CalendarReminder;
use humhub\modules\content\components\ContentContainerActiveRecord;

class ReminderSettings extends Model
{
    /**
     * @var ContentContainerActiveRecord
     */
    public $container;

    /**
     * @var Remindable
     */
    public $entry;

    /**
     * @var User
     */
    public $user;

    /**
     * @var CalendarReminder[]
     */
    public $reminder;

    public function init()
    {
        parent::init();
        $this->loadReminder();

    }

    public function loadReminder()
    {
        if(!$this->entry) {
            $this->reminder = CalendarReminder::getDefaults($this->container, true);
        } else {
            $this->reminder = CalendarReminder::getEntryLevelReminder($this->entry, $this->user, true);
        }

        $this->reminder[] = new CalendarReminder();
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
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     */
    public function load($data, $formName = null)
    {
        if(isset($data[CalendarReminder::instance()->formName()])) {
            $this->clearReminder();
            $this->reminder = [];
            foreach ($data[CalendarReminder::instance()->formName()] as $reminderData) {
                $reminder = $this->initReminder();
                $reminder->load($reminderData, '');
                $this->reminder[] = $reminder;
            }
            return true;
        }

        return false;
    }

    public function save()
    {
        foreach ($this->reminder as $reminder)
        {
            if($this->entry) {
                $reminder->content_id = $this->entry->getContentRecord()->id;
            }
            $reminder->save();
        }

        return true;
    }

    public function initReminder()
    {
       if($this->entry) {
           return CalendarReminder::initEntryLevel(null, null, $this->entry, $this->user);
       } else if($this->container) {
           return CalendarReminder::initContainerDefault(null, null, $this->container);
       } else {
           return CalendarReminder::initGlobalDefault(null, null);
       }
    }

    /**
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function clearReminder()
    {
        if($this->entry) {
            CalendarReminder::clearEntryLevelReminder($this->entry, $this->user);
        } else {
            CalendarReminder::clearDefaults($this->container);
        }
    }
}