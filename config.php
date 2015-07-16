<?php

use humhub\modules\calendar\Module;
use humhub\modules\calendar\Events;
use humhub\modules\space\widgets\Menu;
use humhub\modules\user\widgets\ProfileMenu;
use humhub\modules\space\widgets\Sidebar;
use humhub\modules\user\widgets\ProfileSidebar;
use humhub\widgets\TopMenu;

return array(
    'id' => 'calendar',
    'class' => 'humhub\modules\calendar\Module',
    'namespace' => 'humhub\modules\calendar',
    'events' => array(
        array('class' => Menu::className(), 'event' => Menu::EVENT_INIT, 'callback' => array('humhub\modules\calendar\Events', 'onSpaceMenuInit')),
        array('class' => ProfileMenu::className(), 'event' => ProfileMenu::EVENT_INIT, 'callback' => array('humhub\modules\calendar\Events', 'onProfileMenuInit')),
        array('class' => Sidebar::className(), 'event' => Sidebar::EVENT_INIT, 'callback' => array('humhub\modules\calendar\Events', 'onSpaceSidebarInit')),
        array('class' => ProfileSidebar::className(), 'event' => ProfileSidebar::EVENT_INIT, 'callback' => array('humhub\modules\calendar\Events', 'onProfileSidebarInit')),
        array('class' => humhub\modules\dashboard\widgets\Sidebar::className(), 'event' => humhub\modules\dashboard\widgets\Sidebar::EVENT_INIT, 'callback' => array('humhub\modules\calendar\Events', 'onDashboardSidebarInit')),
        array('class' => TopMenu::className(), 'event' => TopMenu::EVENT_INIT, 'callback' => array('humhub\modules\calendar\Events', 'onTopMenuInit')),
    ),
);
?>