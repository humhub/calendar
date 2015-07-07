<?php

use module\calendar\Module;
use module\calendar\Events;
use humhub\modules\space\widgets\Menu;
use humhub\modules\user\widgets\ProfileMenu;
use humhub\modules\space\widgets\Sidebar;
use humhub\modules\user\widgets\ProfileSidebar;
use humhub\widgets\TopMenu;

return array(
    'id' => 'calendar',
    'class' => Module::className(),
    'events' => array(
        array('class' => Menu::className(), 'event' => Menu::EVENT_INIT, 'callback' => array(Events::className(), 'onSpaceMenuInit')),
        array('class' => ProfileMenu::className(), 'event' => ProfileMenu::EVENT_INIT, 'callback' => array(Events::className(), 'onProfileMenuInit')),
        array('class' => Sidebar::className(), 'event' => Sidebar::EVENT_INIT, 'callback' => array(Events::className(), 'onSpaceSidebarInit')),
        array('class' => ProfileSidebar::className(), 'event' => ProfileSidebar::EVENT_INIT, 'callback' => array(Events::className(), 'onProfileSidebarInit')),
        array('class' => humhub\modules\dashboard\widgets\Sidebar::className(), 'event' => humhub\modules\dashboard\widgets\Sidebar::EVENT_INIT, 'callback' => array(Events::className(), 'onDashboardSidebarInit')),
        array('class' => TopMenu::className(), 'event' => TopMenu::EVENT_INIT, 'callback' => array(Events::className(), 'onTopMenuInit')),
    ),
);
?>