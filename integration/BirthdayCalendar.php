<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\integration;

use DateTime;
use humhub\modules\calendar\interfaces\CalendarItem;
use humhub\modules\meeting\models\Meeting;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use Yii;
use yii\base\Component;
use yii\helpers\Html;

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
    const DEFAULT_COLOR = '#59D6E4';

    const ITEM_TYPE_KEY = 'birthday';

    /**
     * @param $event \humhub\modules\calendar\interfaces\CalendarItemTypesEvent
     * @return mixed
     */
    public static function addItemTypes($event)
    {
        $event->addType(static::ITEM_TYPE_KEY, [
            'title' => Yii::t('CalendarModule.base', 'Birthday'),
            'color' => static::DEFAULT_COLOR,
            'icon' => 'fa-calendar-o'
        ]);
    }

    /**
     * @param $event \humhub\modules\calendar\interfaces\CalendarItemsEvent
     */
    public static function addItems($event)
    {
        /* @var $meetings Meeting[] */
        $users = BirthdayCalendarQuery::findForEvent($event);

        $items = [];
        foreach ($users as $user) {
            /** @var $user User **/
            $upcomingBirthday = new DateTime($user->getAttribute('next_birthday'));
            $items[] = [
                'start' => $upcomingBirthday,
                'end' => $upcomingBirthday,
                'allDay' => true,
                'title' => static::getTitle($user),
                'icon' => 'fa-birthday-cake',
                'openUrl' => $user->getUrl(),
                'viewUrl' => $user->getUrl(),
                'viewMode' => CalendarItem::VIEW_MODE_REDIRECT,
                'editable' => false,
            ];
        }

        $event->addItems(static::ITEM_TYPE_KEY, $items);
    }



    public static function getTitle(User $user)
    {
        return Yii::t('CalendarModule.base', '{displayName} Birthday', ['displayName' => Html::encode($user->getDisplayName())]);
    }

}