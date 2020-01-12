<?php

namespace humhub\modules\calendar;

use humhub\modules\calendar\interfaces\event\EditableEventIF;
use Yii;
use humhub\modules\calendar\interfaces\event\CalendarItemTypesEvent;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\integration\BirthdayCalendar;
use humhub\modules\calendar\interfaces\reminder\CalendarEventReminderIF;
use humhub\modules\calendar\models\reminder\ReminderService;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\calendar\models\reminder\CalendarReminderSent;
use humhub\modules\calendar\models\SnippetModuleSettings;
use humhub\modules\calendar\widgets\DownloadIcsLink;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\widgets\ReminderLink;
use humhub\modules\calendar\widgets\UpcomingEvents;
use humhub\modules\content\models\Content;
use humhub\modules\calendar\helpers\Url;
use yii\db\StaleObjectException;
use yii\helpers\Console;

/**
 * Description of CalendarEvents
 *
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
        require Yii::getAlias('@calendar/vendor/autoload.php');
    }

    /**
     * @param $event CalendarItemTypesEvent
     * @return mixed
     */
    public static function onGetCalendarItemTypes($event)
    {
        BirthdayCalendar::addItemTypes($event);
    }

    /**
     * @param $event \humhub\modules\calendar\interfaces\event\CalendarItemsEvent;
     * @throws \Throwable
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
        $eventModel = static::getCalendarEvent($event->sender->object);

        if(!$eventModel) {
            return;
        }

        if ($eventModel instanceof ContentActiveRecord && $eventModel instanceof CalendarEventIF) {
            $event->sender->addWidget(DownloadIcsLink::class, ['calendarEntry' => $eventModel]);
        }

        if($eventModel instanceof CalendarEventReminderIF) {
            $event->sender->addWidget(ReminderLink::class, ['entry' => $eventModel]);
        }
    }

    private static function getCalendarEvent($model)
    {
        if($model instanceof CalendarEventIF) {
            return $model;
        }

        if(method_exists($model, 'getCalendarEvent')) {
            $event = $model->getCalendarEvent();
            if($event instanceof CalendarEventIF) {
                return $event;
            }
        }

        return null;
    }

    public static function onRecordBeforeInsert($event)
    {
        static::onRecordBeforeUpdate($event);
    }

    public static function onRecordBeforeUpdate($event)
    {
        $model = CalendarUtils::getCalendarEvent($event->sender);
        if($model && ($model instanceof EditableEventIF)) {
            /** @var $model EditableEventIF **/
            if(empty($model->getUid())) {
                $model->setUid(CalendarUtils::generateEventUid($model));
            }
        }
    }

    /**
     * @param $event
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public static function onRecordBeforeDelete($event)
    {
        $model = CalendarUtils::getCalendarEvent($event->sender);

        if(!$model || !($model instanceof CalendarEventReminderIF)) {
            return;
        }

        foreach(CalendarReminder::getEntryLevelReminder($model) as $reminder) {
            $reminder->delete();
        }

        if($model instanceof RecurrentEventIF) {
            $model->getRecurrenceQuery()->onDelete();
        }
    }

    /**
     * @param $event
     * @throws \Throwable
     * @throws StaleObjectException
     */
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

    public static function onCronRun($event)
    {
        static::onBeforeRequest();

        /* @var $module Module */
        $module = Yii::$app->getModule('calendar');
        $lastRunTS = $module->settings->get('lastReminderRunTS');

        if(!$lastRunTS || ((time() - $lastRunTS) >= $module->getRemidnerProcessIntervalS())) {
            try {
                $controller = $event->sender;
                $controller->stdout("Running reminder process... ");
                (new ReminderService())->sendAllReminder();
                $controller->stdout('done.' . PHP_EOL, Console::FG_GREEN);
            } catch (\Throwable $e) {
                Yii::error($e);
                $controller->stdout('error.' . PHP_EOL, Console::FG_RED);
                $controller->stderr("\n".$e->getTraceAsString()."\n", Console::BOLD);
            }
            $module->settings->set('lastReminderRunTS', time());
        }
    }
}
