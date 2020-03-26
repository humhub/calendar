<?php

use humhub\components\Application;
use humhub\modules\space\widgets\Menu;
use humhub\modules\user\models\User;
use humhub\modules\user\widgets\ProfileMenu;
use humhub\modules\space\widgets\Sidebar as SpaceSidebar;
use humhub\modules\dashboard\widgets\Sidebar as DashboardSidebar;
use humhub\modules\user\widgets\ProfileSidebar;
use humhub\widgets\TopMenu;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\calendar\Events;
use humhub\modules\content\widgets\WallEntryLinks;
use humhub\commands\IntegrityController;
use humhub\commands\CronController;

return [
    'id' => 'calendar',
    'class' => 'humhub\modules\calendar\Module',
    'namespace' => 'humhub\modules\calendar',
    'events' => [
        ['class' => Menu::class, 'event' => Menu::EVENT_INIT, 'callback' => [Events::class, 'onSpaceMenuInit']],
        ['class' => Application::class, 'event' => Application::EVENT_BEFORE_REQUEST, 'callback' => [Events::class, 'onBeforeRequest']],
        ['class' => ProfileMenu::class, 'event' => ProfileMenu::EVENT_INIT, 'callback' => [Events::class, 'onProfileMenuInit']],
        ['class' => SpaceSidebar::class, 'event' => SpaceSidebar::EVENT_INIT, 'callback' => [Events::class, 'onSpaceSidebarInit']],
        ['class' => ProfileSidebar::class, 'event' => ProfileSidebar::EVENT_INIT, 'callback' => [Events::class, 'onProfileSidebarInit']],
        ['class' => DashboardSidebar::class, 'event' =>DashboardSidebar::EVENT_INIT, 'callback' => [Events::class, 'onDashboardSidebarInit']],
        ['class' => TopMenu::class, 'event' => TopMenu::EVENT_INIT, 'callback' => [Events::class, 'onTopMenuInit']],
        ['class' => 'humhub\modules\calendar\interfaces\CalendarService', 'event' => 'getItemTypes', 'callback' => [Events::class, 'onGetCalendarItemTypes']],
        ['class' => 'humhub\modules\calendar\interfaces\CalendarService', 'event' => 'findItems', 'callback' => [Events::class, 'onFindCalendarItems']],
        ['class' => WallEntryLinks::class, 'event' => WallEntryLinks::EVENT_INIT, 'callback' => [Events::class, 'onWallEntryLinks']],
        ['class' => ContentActiveRecord::class, 'event' => ContentActiveRecord::EVENT_BEFORE_DELETE, 'callback' => [Events::class, 'onRecordBeforeDelete']],
        ['class' => ContentActiveRecord::class, 'event' => ContentActiveRecord::EVENT_BEFORE_INSERT, 'callback' => [Events::class, 'onRecordBeforeInsert']],
        ['class' => ContentActiveRecord::class, 'event' => ContentActiveRecord::EVENT_BEFORE_UPDATE, 'callback' => [Events::class, 'onRecordBeforeUpdate']],
        ['class' => IntegrityController::class, 'event' => IntegrityController::EVENT_ON_RUN, 'callback' => [Events::class, 'onIntegrityCheck']],
        ['class' => CronController::class, 'event' => CronController::EVENT_BEFORE_ACTION, 'callback' => [Events::class, 'onCronRun']],
        ['class' => User::class, 'event' => User::EVENT_BEFORE_DELETE, 'callback' => [Events::class, 'onUserDelete']],
    ],
];