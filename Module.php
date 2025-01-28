<?php

namespace humhub\modules\calendar;

use humhub\components\console\Application as ConsoleApplication;
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
     * @var string the default route of this module.
     */
    public $defaultRoute = 'view';

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
     * @var bool whether or not to include the ORGANIZER in ICS export
     */
    public $icsOrganizer = false;

    /**
     * @inheritdoc
     */
    public $resourcesPath = 'resources';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (Yii::$app instanceof ConsoleApplication) {
            // Prevents the Yii HelpCommand from crawling all web controllers and possibly throwing errors at REST endpoints if the REST module is not available.
            $this->controllerNamespace = 'calendar/commands';
        }
    }

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
            $entry->hardDelete();
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
            $entry->hardDelete();
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
     * @return Module
     */
    public static function instance()
    {
        return Yii::$app->getModule('calendar');
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

    /**
     * @inheritdoc
     */
    public function getContentClasses(): array
    {
        return [CalendarEntry::class];
    }
}
