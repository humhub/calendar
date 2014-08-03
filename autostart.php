<?php

Yii::app()->moduleManager->register(array(
    'id' => 'calendar',
    'class' => 'application.modules.calendar.CalendarModule',
    'import' => array(
        'application.modules.calendar.*',
        'application.modules.calendar.models.*',
        'application.modules.calendar.notifications.*',
    ),
    'events' => array(
        array('class' => 'SpaceMenuWidget', 'event' => 'onInit', 'callback' => array('CalendarModuleEvents', 'onSpaceMenuInit')),
        array('class' => 'ProfileMenuWidget', 'event' => 'onInit', 'callback' => array('CalendarModuleEvents', 'onProfileMenuInit')),
        array('class' => 'SpaceSidebarWidget', 'event' => 'onInit', 'callback' => array('CalendarModuleEvents', 'onSpaceSidebarInit')),
        array('class' => 'ProfileSidebarWidget', 'event' => 'onInit', 'callback' => array('CalendarModuleEvents', 'onProfileSidebarInit')),
        array('class' => 'DashboardSidebarWidget', 'event' => 'onInit', 'callback' => array('CalendarModuleEvents', 'onDashboardSidebarInit')),
        array('class' => 'TopMenuWidget', 'event' => 'onInit', 'callback' => array('CalendarModuleEvents', 'onTopMenuInit')),
    ),
));
?>