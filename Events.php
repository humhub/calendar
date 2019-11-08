<?php

namespace humhub\modules\calendar;

use Yii;
use humhub\modules\calendar\integration\BirthdayCalendar;
use humhub\modules\calendar\interfaces\Remindable;
use humhub\modules\calendar\interfaces\ReminderService;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\calendar\models\reminder\CalendarReminderSent;
use humhub\modules\calendar\models\SnippetModuleSettings;
use humhub\modules\calendar\widgets\DownloadIcsLink;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\widgets\ReminderLink;
use humhub\modules\calendar\widgets\UpcomingEvents;
use humhub\modules\content\models\Content;
use humhub\modules\calendar\helpers\Url;

/**
 * Description of CalendarEvents
 *EE
 * @author luke
 */
class Events
{
    /**
     * @inheritdoc
     */
    public static function onBeforeRequest()
    {
        static::registerAutoloader();
        Yii::$app->getModule('calendar')->set(CalendarService::class, ['class' => CalendarService::class]);
    }

    /**
     * Register composer autoloader when Reader not found
     */
    public static function registerAutoloader()
    {
        if (class_exists('\Sabre\VObject')) {
            return;
        }

        require Yii::getAlias('@calendar/vendor/autoload.php');
    }

    /**
     * @param $event \humhub\modules\calendar\interfaces\CalendarItemTypesEvent
     * @return mixed
     */
    public static function onGetCalendarItemTypes($event)
    {
        BirthdayCalendar::addItemTypes($event);
    }

    /**
     * @param $event \humhub\modules\calendar\interfaces\CalendarItemsEvent;
     */
    public static function onFindCalendarItems($event)
    {
        BirthdayCalendar::addItems($event);
    }

    public static function onTopMenuInit($event)
    {
        if (SnippetModuleSettings::instantiate()->showGlobalCalendarItems()) {
            $event->sender->addItem([
                'label' => Yii::t('CalendarModule.base', 'Calendar'),
                'url' => Url::toGlobalCalendar(),
                'icon' => '<i class="fa fa-calendar"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'calendar' && Yii::$app->controller->id == 'global'),
                'sortOrder' => 300,
            ]);
        }
    }

    public static function onSpaceMenuInit($event)
    {
        $space = $event->sender->space;
        if ($space->isModuleEnabled('calendar')) {
            $event->sender->addItem([
                'label' => Yii::t('CalendarModule.base', 'Calendar'),
                'group' => 'modules',
                'url' => Url::toCalendar($space),
                'icon' => '<i class="fa fa-calendar"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'calendar'),

            ]);
        }
    }

    public static function onProfileMenuInit($event)
    {
        $user = $event->sender->user;
        if ($user->isModuleEnabled('calendar')) {
            $event->sender->addItem([
                'label' => Yii::t('CalendarModule.base', 'Calendar'),
                'url' => Url::toCalendar($user),
                'icon' => '<i class="fa fa-calendar"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'calendar'),
            ]);
        }
    }

    public static function onSpaceSidebarInit($event)
    {
        $space = $event->sender->space;
        $settings = SnippetModuleSettings::instantiate();

        if ($space->isModuleEnabled('calendar')) {
            if ($settings->showUpcomingEventsSnippet()) {
                $event->sender->addWidget(UpcomingEvents::class, ['contentContainer' => $space], ['sortOrder' => $settings->upcomingEventsSnippetSortOrder]);
            }
        }
    }

    public static function onDashboardSidebarInit($event)
    {
        $settings = SnippetModuleSettings::instantiate();

        if ($settings->showUpcomingEventsSnippet()) {
            $event->sender->addWidget(UpcomingEvents::class, [], ['sortOrder' => $settings->upcomingEventsSnippetSortOrder]);
        }
    }

    public static function onProfileSidebarInit($event)
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $user = $event->sender->user;
        if ($user != null) {
            $settings = SnippetModuleSettings::instantiate();

            if ($settings->showUpcomingEventsSnippet()) {
                $event->sender->addWidget(UpcomingEvents::class, ['contentContainer' => $user], ['sortOrder' => $settings->upcomingEventsSnippetSortOrder]);
            }
        }
    }

    public static function onWallEntryLinks($event)
    {
        if ($event->sender->object instanceof CalendarEntry) {
            $event->sender->addWidget(DownloadIcsLink::class, ['calendarEntry' => $event->sender->object]);
        }

        if($event->sender->object instanceof Remindable) {
            $event->sender->addWidget(ReminderLink::class, ['entry' => $event->sender->object]);
        }
    }

    public static function onContentDelete($event)
    {
        if(!($event->sender instanceof Remindable)) {
            return;
        }

        foreach(CalendarReminder::getEntryLevelReminder($event->sender) as $reminder) {
            $reminder->delete();
        }
    }

    public static function onIntegrityCheck($event)
    {
        $integrityController = $event->sender;
        $integrityController->showTestHeadline("Calendar Module (" . CalendarReminder::find()->count() . " reminder entries)");

        foreach (CalendarReminder::find()->all() as $reminder) {
            if ($reminder->isEntryLevelReminder() && !Content::findOne(['id' => $reminder->content_id])) {
                if ($integrityController->showFix("Delete calendar reminder " . $reminder->id . " without existing entry relation!")) {
                    $reminder->delete();
                }
            }
        }

        $integrityController->showTestHeadline("Calendar Module (" . CalendarReminderSent::find()->count() . " reminder sent entries)");

        foreach (CalendarReminderSent::find()->all() as $reminderSent) {
            if(!Content::findOne(['id' => $reminderSent->content_id])) {
                if ($integrityController->showFix("Delete calendar reminder sent" . $reminderSent->id . " without existing entry relation!")) {
                    $reminderSent->delete();
                }
            }
        }
    }

    public static function onCronRun()
    {
        /* @var $module Module */
        $module = Yii::$app->module->getModule('calendar');

        $lastRunTS = $module->settings->get('lastReminderRunTS');

        if(!$lastRunTS || ((time() - $lastRunTS) >= $module->getRemidnerProcessIntervalMS())) {
            (new ReminderService())->sendAllReminder();
            $module->settings->set('lastReminderRunTS', time());
        }
    }
}
