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
        array('class' => 'SpaceMenuWidget', 'event' => 'onInit', 'callback' => array('CalendarModule', 'onSpaceMenuInit')),
        array('class' => 'ProfileMenuWidget', 'event' => 'onInit', 'callback' => array('CalendarModule', 'onProfileMenuInit')),
        array('class' => 'SpaceSidebarWidget', 'event' => 'onInit', 'callback' => array('CalendarModule', 'onSpaceSidebarInit')),        
        array('class' => 'ProfileSidebarWidget', 'event' => 'onInit', 'callback' => array('CalendarModule', 'onProfileSidebarInit')),        
    ),
));
?>