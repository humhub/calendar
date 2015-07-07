<?php

namespace module\calendar;

use Yii;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use module\calendar\models\CalendarEntry;

class Module extends \humhub\components\Module
{

    public function behaviors()
    {
        return [
            \humhub\modules\user\behaviors\UserModule::className(),
            \humhub\modules\space\behaviors\SpaceModule::className(),
        ];
    }

    public function disable()
    {
        if (parent::disable()) {

            foreach (CalendarEntry::find()->all() as $entry) {
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
        foreach (CalendarEntry::find()->contentContainer($space)->all() as $entry) {
            $entry->delete();
        }
    }

    public function disableUserModule(User $user)
    {
        foreach (CalendarEntry::find()->contentContainer($user)->all() as $entry) {
            $entry->delete();
        }
    }

}
