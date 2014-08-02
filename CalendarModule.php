<?php

class CalendarModule extends HWebModule
{

    public function behaviors()
    {
        return array(
            'SpaceModuleBehavior' => array(
                'class' => 'application.modules_core.space.behaviors.SpaceModuleBehavior',
            ),
            'UserModuleBehavior' => array(
                'class' => 'application.modules_core.user.behaviors.UserModuleBehavior',
            ),
        );
    }

    public function disable()
    {
        if (parent::disable()) {

            foreach (CalendarEntry::model()->findAll() as $entry) {
                $entry->delete();
            }

            return true;
        }

        return false;
    }

    public function getSpaceModuleDescription()
    {
        return Yii::t('CalendarModule.base', 'Adds an event calendar to this space.');
    }

    public function getUserModuleDescription()
    {
        return Yii::t('CalendarModule.base', 'Adds an calendar for private or public events to your profile and mainmenu.');
    }

    public function disableSpaceModule(Space $space)
    {
        foreach (CalendarEntry::model()->contentContainer($space)->findAll() as $entry) {
            $entry->delete();
        }
    }

    public function disableUserModule(User $user)
    {
        foreach (CalendarEntry::model()->contentContainer($user)->findAll() as $entry) {
            $entry->delete();
        }
    }

    public static function onSpaceMenuInit($event)
    {

        $space = Yii::app()->getController()->getSpace();

        if ($space->isModuleEnabled('calendar')) {

            $event->sender->addItem(array(
                'label' => Yii::t('CalendarModule.base', 'Calendar'),
                'url' => Yii::app()->createUrl('//calendar/view/index', array('sguid' => $space->guid)),
                'icon' => '<i class="fa fa-calendar"></i>',
                'isActive' => (Yii::app()->controller->module && Yii::app()->controller->module->id == 'calendar'),
            ));
        }
    }

    public static function onProfileMenuInit($event)
    {

        $user = Yii::app()->getController()->getUser();

        // Is Module enabled on this workspace?
        if ($user->isModuleEnabled('calendar')) {
            $event->sender->addItem(array(
                'label' => Yii::t('CalendarModule.base', 'Calendar'),
                'url' => Yii::app()->createUrl('//calendar/view/index', array('uguid' => $user->guid)),
                'isActive' => (Yii::app()->controller->module && Yii::app()->controller->module->id == 'calendar'),
            ));
        }
    }

    public static function onSpaceSidebarInit($event)
    {
        $space = null;

        if (isset(Yii::app()->params['currentSpace'])) {
            $space = Yii::app()->params['currentSpace'];
        }

        if (Yii::app()->getController() instanceof ContentContainerController && Yii::app()->getController()->contentContainer instanceof Space) {
            $space = Yii::app()->getController()->contentContainer;
        }

        if ($space != null) {
            if ($space->isModuleEnabled('calendar')) {
                $event->sender->addWidget('application.modules.calendar.widgets.NextEventsSidebarWidget', array('contentContainer' => $space), array('sortOrder' => 550));
            }
        }
    }

    public static function onProfileSidebarInit($event)
    {
        $user = null;

        if (isset(Yii::app()->params['currentUser'])) {
            $user = Yii::app()->params['currentUser'];
        }

        if (Yii::app()->getController() instanceof ContentContainerController && Yii::app()->getController()->contentContainer instanceof User) {
            $user = Yii::app()->getController()->contentContainer;
        }

        if ($user != null) {
            if ($user->isModuleEnabled('calendar')) {
                $event->sender->addWidget('application.modules.calendar.widgets.NextEventsSidebarWidget', array('contentContainer' => $user), array('sortOrder' => 550));
            }
        }
    }

}
