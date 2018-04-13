<?php

namespace humhub\modules\calendar;

use humhub\modules\calendar\integration\BirthdayCalendar;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\SnippetModuleSettings;
use humhub\modules\calendar\widgets\DownloadIcsLink;
use humhub\modules\calendar\widgets\UpcomingEvents;
use Yii;
use yii\helpers\Url;

/**
 * Description of CalendarEvents
 *
 * @author luke
 */
class Events extends \yii\base\Object
{

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
                'url' => Url::to(['/calendar/global/index']),
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
                'url' => $space->createUrl('/calendar/view/index'),
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
                'url' => $user->createUrl('/calendar/view/index'),
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
    }

}
