<?php

/**
 * HumHub
 * Copyright © 2014 The HumHub Project
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 */

/**
 * Description of CalendarEvents
 *
 * @author luke
 */
class CalendarModuleEvents
{

    public static function onTopMenuInit($event)
    {
        $user = Yii::app()->user->getModel();
        if ($user->isModuleEnabled('calendar')) {
            $event->sender->addItem(array(
                'label' => Yii::t('CalendarModule.base', 'Calendar'),
                'url' => Yii::app()->createUrl('//calendar/global/index', array('uguid' => Yii::app()->user->guid)),
                'icon' => '<i class="fa fa-calendar"></i>',
                'isActive' => (Yii::app()->controller->module && Yii::app()->controller->module->id == 'calendar' && Yii::app()->controller->id == 'global'),
                'sortOrder' => 300,
            ));
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

    public static function onDashboardSidebarInit($event)
    {
        $user = Yii::app()->user->getModel();
        if ($user->isModuleEnabled('calendar')) {
            $event->sender->addWidget('application.modules.calendar.widgets.NextEventsSidebarWidget', array(), array('sortOrder' => 550));
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
