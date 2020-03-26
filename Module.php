<?php

namespace humhub\modules\calendar;

use humhub\modules\calendar\models\CalendarEntryType;
use Yii;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\components\ContentContainerActiveRecord;

class Module extends ContentContainerModule
{
    /**
     * @var bool feature switch for recurrence events
     */
    public $recurrenceActive = true;

    /**
     * @var int Reminder process run interval in minutes
     */
    public $reminderProcessInterval = 10;

    /**
     * @var int Defines the maximum number of events the reminder process can handle at once
     */
    public $reminderProcessEventLimit = 500;

    /**
     * @var int max amount of reminder allowed in the reminder settings
     */
    public $maxReminder = 3;

    /**
     * @inheritdoc
     */
    public $resourcesPath = 'resources';

    /**
     * @return bool
     */
    public static function isRecurrenceActive()
    {
        /* @var $module static */
        $module = Yii::$app->getModule('calendar');
        return $module ? $module->recurrenceActive : true;
    }

    /**
     * @inheritdoc
     */
    public static function onBeforeRequest()
    {
        static::registerAutoloader();
    }

    /**
     * Register composer autoloader when Reader not found
     */
    public static function registerAutoloader()
    {
        if (class_exists('\Sabre\VObject\Component\VCalendar')) {
            return;
        }

        require Yii::getAlias('@calendar/vendor/autoload.php');
    }

    public function getRemidnerProcessIntervalS()
    {
        return $this->reminderProcessInterval * 60;
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerTypes()
    {
        return [
            Space::class,
            User::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function disable()
    {
        foreach (CalendarEntry::find()->all() as $entry) {
            $entry->delete();
        }

        CalendarEntryType::deleteByModule();
        parent::disable();
    }

    /**
     * @inheritdoc
     */
    public function disableContentContainer(ContentContainerActiveRecord $container)
    {
        parent::disableContentContainer($container);
        foreach (CalendarEntry::find()->contentContainer($container)->all() as $entry) {
            $entry->delete();
        }

        CalendarEntryType::deleteByModule($container);
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerName(ContentContainerActiveRecord $container)
    {
        return Yii::t('CalendarModule.base', 'Calendar');
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        if ($container instanceof Space) {
            return Yii::t('CalendarModule.base', 'Adds an event calendar to this space.');
        } elseif ($container instanceof User) {
            return Yii::t('CalendarModule.base', 'Adds a calendar for private or public events to your profile and main menu.');
        }
    }

    public function getContentContainerConfigUrl(ContentContainerActiveRecord $container)
    {
        return Url::toConfig($container);
    }

    public function getConfigUrl()
    {
        return Url::toConfig();
    }

    /**
     * @inheritdoc
     */
    public function getContainerPermissions($contentContainer = null)
    {
        if ($contentContainer !== null) {
            return [
                new permissions\CreateEntry(),
                new permissions\ManageEntry(),
            ];
        }
        return [];
    }
}
