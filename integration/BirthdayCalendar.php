<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\integration;

use Yii;
use yii\base\Component;
use yii\helpers\Html;
use humhub\modules\calendar\interfaces\event\CalendarItemTypesEvent;
use humhub\modules\user\models\User;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 14.09.2017
 * Time: 12:28
 *
 * @todo change base class back to BaseObject after v1.3 is stable
 */

class BirthdayCalendar extends Component
{
    /**
     * Default color of meeting type calendar items.
     */
    public const DEFAULT_COLOR = '#59D6E4';

    public const ITEM_TYPE_KEY = 'birthday';

    /**
     * @param $event CalendarItemTypesEvent
     * @return mixed
     */
    public static function addItemTypes($event)
    {
        $event->addType(BirthdayCalendarType::ITEM_TYPE_KEY, new BirthdayCalendarType());
    }

    /**
     * @param $event \humhub\modules\calendar\interfaces\event\CalendarItemsEvent
     * @throws \Throwable
     */
    public static function addItems($event)
    {
        /* @var $meetings BirthdayCalendarEntry[] */
        $users = BirthdayCalendarQuery::findForEvent($event);

        foreach ($users as $user) {
            $item = new BirthdayCalendarEntry(['model' => $user]);
            $event->addItems(BirthdayCalendarType::ITEM_TYPE_KEY, $item);
        }
    }



    public static function getTitle(User $user)
    {
        return Yii::t('CalendarModule.base', '{displayName} Birthday', ['displayName' => Html::encode($user->getDisplayName())]);
    }

}
