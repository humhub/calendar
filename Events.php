<?php

namespace humhub\modules\calendar;

use DateTime;
use humhub\modules\calendar\extensions\custom_pages\elements\CalendarEntryElement;
use humhub\modules\calendar\extensions\custom_pages\elements\CalendarEventsElement;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\MenuSettings;
use humhub\modules\content\events\ContentEvent;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\calendar\interfaces\event\EditableEventIF;
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
use Yii;
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
        try {
            static::registerAutoloader();
            Yii::$app->getModule('calendar')->set(CalendarService::class, ['class' => CalendarService::class]);
        } catch (\Throwable $e) {
            Yii::error($e);
        }

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
        try {
            BirthdayCalendar::addItemTypes($event);
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    /**
     * @param $event \humhub\modules\calendar\interfaces\event\CalendarItemsEvent;
     * @throws \Throwable
     */
    public static function onFindCalendarItems($event)
    {
        try {
            BirthdayCalendar::addItems($event);
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onTopMenuInit($event)
    {
        try {
            if (SnippetModuleSettings::instance()->showGlobalCalendarItems() &&
                MenuSettings::instance()->show) {
                $event->sender->addItem([
                    'label' => Yii::t('CalendarModule.base', 'Calendar'),
                    'url' => Url::toGlobalCalendar(),
                    'icon' => '<i class="fa fa-calendar"></i>',
                    'isActive' => (Yii::$app->controller->module
                        && Yii::$app->controller->module->id == 'calendar'
                        && Yii::$app->controller->id == 'global'),
                    'sortOrder' => MenuSettings::instance()->sortOrder,
                ]);
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onSpaceMenuInit($event)
    {
        try {
            /* @var Space $space */
            $space = $event->sender->space;
            if ($space->moduleManager->isEnabled('calendar')) {
                $event->sender->addItem([
                    'label' => Yii::t('CalendarModule.base', 'Calendar'),
                    'group' => 'modules',
                    'url' => Url::toCalendar($space),
                    'icon' => '<i class="fa fa-calendar"></i>',
                    'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'calendar'),

                ]);
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onProfileMenuInit($event)
    {
        try {
            /* @var User $user */
            $user = $event->sender->user;
            if ($user->moduleManager->isEnabled('calendar')) {
                $event->sender->addItem([
                    'label' => Yii::t('CalendarModule.base', 'Calendar'),
                    'url' => Url::toCalendar($user),
                    'icon' => '<i class="fa fa-calendar"></i>',
                    'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'calendar'),
                ]);
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onSpaceSidebarInit($event)
    {
        try {
            /* @var Space $space */
            $space = $event->sender->space;
            $settings = SnippetModuleSettings::instantiate();

            if ($space->moduleManager->isEnabled('calendar')) {
                if ($settings->showUpcomingEventsSnippet()) {
                    $event->sender->addWidget(UpcomingEvents::class, ['contentContainer' => $space], ['sortOrder' => $settings->upcomingEventsSnippetSortOrder]);
                }
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onDashboardSidebarInit($event)
    {
        try {
            $settings = SnippetModuleSettings::instantiate();

            if ($settings->showUpcomingEventsSnippet()) {
                $event->sender->addWidget(UpcomingEvents::class, [], ['sortOrder' => $settings->upcomingEventsSnippetSortOrder]);
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onProfileSidebarInit($event)
    {
        try {
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
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onWallEntryLinks($event)
    {
        try {
            $eventModel = static::getCalendarEvent($event->sender->object);

            if (!$eventModel) {
                return;
            }

            if ($eventModel instanceof ContentActiveRecord && $eventModel instanceof CalendarEventIF) {
                $event->sender->addWidget(DownloadIcsLink::class, ['calendarEntry' => $eventModel]);
            }

            /* @var $eventModel CalendarEventIF */
            if ($eventModel->getStartDateTime() <= new DateTime()) {
                return;
            }

            if ($eventModel instanceof CalendarEventReminderIF && !RecurrenceHelper::isRecurrentRoot($eventModel)) {
                $event->sender->addWidget(ReminderLink::class, ['entry' => $eventModel]);
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    /**
     * @param $model
     * @return CalendarEventIF|null
     */
    private static function getCalendarEvent($model)
    {
        if ($model instanceof CalendarEventIF) {
            return $model;
        }

        if (method_exists($model, 'getCalendarEvent')) {
            $event = $model->getCalendarEvent();
            if ($event instanceof CalendarEventIF) {
                return $event;
            }
        }

        return null;
    }

    public static function onRecordBeforeInsert($event)
    {
        try {
            static::onRecordBeforeUpdate($event);
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    public static function onRecordBeforeUpdate($event)
    {
        try {
            $model = CalendarUtils::getCalendarEvent($event->sender);
            if ($model && ($model instanceof EditableEventIF)) {
                /** @var $model EditableEventIF **/
                if (empty($model->getUid())) {
                    $model->setUid(CalendarUtils::generateEventUid($model));
                }
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }

    /**
     * @param $event
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public static function onRecordBeforeDelete($event)
    {
        try {
            $model = CalendarUtils::getCalendarEvent($event->sender);

            if (!$model || !($model instanceof CalendarEventReminderIF)) {
                return;
            }

            foreach (CalendarReminder::getEntryLevelReminder($model) as $reminder) {
                $reminder->delete();
            }

            if ($model instanceof RecurrentEventIF) {
                // When deleting duplicates we want to prevent automatic exdate settings.
                if (!static::$duplicateIntegrityRun) {
                    $model->getRecurrenceQuery()->onDelete();
                }
            }
        } catch (\Throwable $e) {
            Yii::error($e);
        }

    }

    public static $duplicateIntegrityRun = false;

    /**
     * @param $event
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public static function onIntegrityCheck($event)
    {
        $integrityController = $event->sender;
        $integrityController->showTestHeadline("Calendar Module (" . CalendarReminder::find()->count() . " reminder entries)");

        foreach (CalendarReminder::find()->each() as $reminder) {
            if ($reminder->isEntryLevelReminder() && !Content::findOne(['id' => $reminder->content_id])) {
                if ($integrityController->showFix("Delete calendar reminder " . $reminder->id . " without existing entry relation!")) {
                    $reminder->delete();
                }
            }
        }

        $integrityController->showTestHeadline("Calendar Module (" . CalendarReminderSent::find()->count() . " reminder sent entries)");

        foreach (CalendarReminderSent::find()->each() as $reminderSent) {
            if (!Content::findOne(['id' => $reminderSent->content_id])) {
                if ($integrityController->showFix("Delete calendar reminder sent" . $reminderSent->id . " without existing entry relation!")) {
                    $reminderSent->delete();
                }
            }
        }

        static::$duplicateIntegrityRun = true;
        $duplicatedRecurrences = CalendarEntry::find()
            ->select('id, parent_event_id, recurrence_id, COUNT(*)')
            ->where('recurrence_id IS NOT NULL')
            ->andWhere('parent_event_id IS NOT NULL')
            ->groupBy('parent_event_id, recurrence_id')
            ->having('COUNT(*) > 1')->asArray(true);

        foreach ($duplicatedRecurrences->each() as $duplicatedRecurrenceArr) {
            $duplicateQuery = CalendarEntry::find()
                ->where(['recurrence_id' => $duplicatedRecurrenceArr['recurrence_id']])
                ->andWhere(['parent_event_id' => $duplicatedRecurrenceArr['parent_event_id']])
                ->andWhere(['<>', 'id', $duplicatedRecurrenceArr['id']]);

            foreach ($duplicateQuery->each() as $duplicate) {
                if (RecurrenceHelper::isRecurrentInstance($duplicate) && $duplicate->id !== $duplicatedRecurrenceArr['id']) {
                    if ($integrityController->showFix('Delete duplicated recurrent event instance ' . $duplicate->id . '!')) {
                        $duplicate->hardDelete();
                    }
                }
            }
        }
        static::$duplicateIntegrityRun = false;
    }

    /**
     * Callback when a user is completely deleted.
     *
     * @param \yii\base\Event $event
     */
    public static function onUserDelete($event)
    {
        $user = $event->sender;
        foreach (CalendarEntryParticipant::findAll(['user_id' => $user->id]) as $participant) {
            $participant->delete();
        }
    }

    public static function onCronRun($event)
    {
        static::onBeforeRequest();

        /* @var $module Module */
        $module = Yii::$app->getModule('calendar');
        $lastRunTS = $module->settings->get('lastReminderRunTS');

        if (!$lastRunTS || ((time() - $lastRunTS) >= $module->getRemidnerProcessIntervalS())) {
            try {
                $controller = $event->sender;
                $controller->stdout("Running reminder process... ");
                (new ReminderService())->sendAllReminder();
                $controller->stdout('done.' . PHP_EOL, Console::FG_GREEN);
            } catch (\Throwable $e) {
                Yii::error($e);
                $controller->stdout('error.' . PHP_EOL, Console::FG_RED);
                $controller->stderr("\n" . $e->getTraceAsString() . "\n", Console::BOLD);
            }
            $module->settings->set('lastReminderRunTS', time());
        }
    }

    public static function onRestApiAddRules()
    {
        /* @var humhub\modules\rest\Module $restModule */
        $restModule = Yii::$app->getModule('rest');
        $restModule->addRules([

            ['pattern' => 'calendar/', 'route' => 'calendar/rest/calendar/find', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'calendar/container/<containerId:\d+>', 'route' => 'calendar/rest/calendar/find-by-container', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'calendar/container/<containerId:\d+>', 'route' => 'calendar/rest/calendar/delete-by-container', 'verb' => 'DELETE'],

            //Calendar entry CRUD
            ['pattern' => 'calendar/container/<containerId:\d+>', 'route' => 'calendar/rest/calendar/create', 'verb' => 'POST'],
            ['pattern' => 'calendar/entry/<id:\d+>', 'route' => 'calendar/rest/calendar/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'calendar/entry/<id:\d+>', 'route' => 'calendar/rest/calendar/update', 'verb' => 'PUT'],
            ['pattern' => 'calendar/entry/<id:\d+>', 'route' => 'calendar/rest/calendar/delete', 'verb' => 'DELETE'],

            //Calendar Entry Management
            ['pattern' => 'calendar/entry/<id:\d+>/upload-files', 'route' => 'calendar/rest/calendar/attach-files', 'verb' => 'POST'],
            ['pattern' => 'calendar/entry/<id:\d+>/remove-file/<fileId:\d+>', 'route' => 'calendar/rest/calendar/remove-file', 'verb' => 'DELETE'],

            //Participate
            ['pattern' => 'calendar/entry/<id:\d+>/respond', 'route' => 'calendar/rest/calendar/respond', 'verb' => 'POST'],

        ], 'calendar');
    }

    public static function onCustomPagesTemplateElementTypeServiceInit($event)
    {
        /* @var \humhub\modules\custom_pages\modules\template\services\ElementTypeService $elementTypeService */
        $elementTypeService = $event->sender;
        $elementTypeService->addType(CalendarEntryElement::class);
        $elementTypeService->addType(CalendarEventsElement::class);
    }

    public static function onContentAfterSoftDelete(ContentEvent $event): void
    {
        // It may be called from wall stream
        if ($event->content->object_model === CalendarEntry::class) {
            /* @var CalendarEntry $calendarEntry */
            $calendarEntry = $event->content->getModel();
            if ($calendarEntry &&
                RecurrenceHelper::isRecurrentInstance($calendarEntry) &&
                $calendarEntry->getRecurrenceRoot()?->content?->state === Content::STATE_PUBLISHED) {
                // Child recurrent entry must be deleted hardly if the parent entry is not soft deleted
                $calendarEntry->hardDelete();
            }
        }
    }

}
