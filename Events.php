<?php

namespace humhub\modules\calendar;

use Yii;
use yii\helpers\Url;
use humhub\modules\calendar\widgets\NextEvents;

/**
 * Description of CalendarEvents
 *
 * @author luke
 */
class Events extends \yii\base\Object
{

    public static function onTopMenuInit($event)
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $user = Yii::$app->user->getIdentity();
        if ($user->isModuleEnabled('calendar')) {
            $event->sender->addItem(array(
                'label' => Yii::t('CalendarModule.base', 'Calendar'),
                'url' => Url::to(['/calendar/global/index']),
                'icon' => '<i class="fa fa-calendar"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'calendar' && Yii::$app->controller->id == 'global'),
                'sortOrder' => 300,
            ));
        }
    }

    public static function onSpaceMenuInit($event)
    {
        $space = $event->sender->space;
        if ($space->isModuleEnabled('calendar')) {
            $event->sender->addItem(array(
                'label' => Yii::t('CalendarModule.base', 'Calendar'),
                'group' => 'modules',
                'url' => $space->createUrl('/calendar/view/index'),
                'icon' => '<i class="fa fa-calendar"></i>',
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'calendar'),
            ));
        }
    }

    public static function onProfileMenuInit($event)
    {
        $user = $event->sender->user;
        if ($user->isModuleEnabled('calendar')) {
            $event->sender->addItem(array(
                'label' => Yii::t('CalendarModule.base', 'Calendar'),
                'url' => $user->createUrl('/calendar/view/index'),
                'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'calendar'),
            ));
        }
    }

    public static function onSpaceSidebarInit($event)
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $space = $event->sender->space;
        if ($space->isModuleEnabled('calendar')) {
            $event->sender->addWidget(NextEvents::className(), array('contentContainer' => $space), array('sortOrder' => 550));
        }
    }

    public static function onDashboardSidebarInit($event)
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $user = Yii::$app->user->getIdentity();
        if ($user->isModuleEnabled('calendar')) {
            $event->sender->addWidget(NextEvents::className(), array(), array('sortOrder' => 550));
        }
    }

    public static function onProfileSidebarInit($event)
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $user = $event->sender->user;
        if ($user != null) {
            if ($user->isModuleEnabled('calendar')) {
                $event->sender->addWidget(NextEvents::className(), array('contentContainer' => $user), array('sortOrder' => 550));
            }
        }
    }

}
