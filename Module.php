<?php

namespace humhub\modules\calendar;

use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\oauth\Module as OAuthModule;
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
     * The OAuth scope a token needs to read calendars and events over CalDAV.
     */
    public const SCOPE_READ = 'calendar:read';

    /**
     * The OAuth scope a token needs to add, change and delete events over CalDAV.
     */
    public const SCOPE_WRITE = 'calendar:write';

    /**
     * The device connection of calendar apps (`DeviceConnection`), also the issuer of their
     * passwords, see `Events::onCollectDeviceConnections()`.
     */
    public const CALDAV_CONNECTION = 'calendar-caldav';

    /**
     * User setting: since when the CalDAV passwords of earlier versions are no longer converted
     * into tokens, set when the user revokes a converted one (see `CalDavAuth`).
     */
    public const CALDAV_LEGACY_CUTOFF = 'caldavLegacyCutoff';

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
     * @deprecated since 1.9.9, the reminder process handles all upcoming events
     */
    public $reminderProcessEventLimit = 500;

    /**
     * @var int max amount of reminder allowed in the reminder settings
     */
    public $maxReminder = 3;


    /**
     * @var bool whether or not to include the ORGANIZER in ICS export
     * @deprecated since 1.7.4
     * @see `Include Organizer and Participant Info in Exports` option in Calendar Module Admin UI
     */
    public $icsOrganizer = false;

    /**
     * Whether calendar apps may sign in over CalDAV: switched on in the export settings, and
     * personal access tokens allowed, which are their passwords.
     */
    public function isCalDavAvailable(): bool
    {
        return (bool)$this->settings->get('enableCalDav', true)
            && OAuthModule::instance()->allowsPersonalAccessTokens();
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
        if (class_exists(\Sabre\VObject\Component\VCalendar::class)) {
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
