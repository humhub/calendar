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
use humhub\modules\calendar\interfaces\CalendarItemWrapper;
use humhub\modules\meeting\models\Meeting;
use humhub\modules\user\models\Profile;
use humhub\widgets\Label;
use Yii;
use yii\base\Object;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 14.09.2017
 * Time: 12:28
 */

class BirthdayCalendar extends Object
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
        $profiles = BirthdayCalendarQuery::findForEvent($event);

        $items = [];
        foreach ($profiles as $profile) {
            /** @var $profile Profile **/
            $upcomingBirthday = BirthdayCalendarQuery::toCurrentYear($profile->birthday);
            $items[] = [
                'start' => $upcomingBirthday,
                'end' => $upcomingBirthday,
                'allDay' => true,
                'title' => static::getTitle($profile),
                'icon' => 'fa-birthday-cake',
                'openUrl' => $profile->user->getUrl(),
                'viewUrl' => $profile->user->getUrl(),
                'viewMode' => CalendarItem::VIEW_MODE_REDIRECT,
                'editable' => true,
            ];
        }

        $event->addItems(static::ITEM_TYPE_KEY, $items);
    }



    public static function getTitle($profile)
    {
        return Yii::t('CalendarModule.base', '{displayName} Birthday', ['displayName' => Html::encode($profile->user->getDisplayName())]);
    }

}