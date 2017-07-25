<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\notifications;

use humhub\modules\notification\components\NotificationCategory;
use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 21.07.2017
 * Time: 23:15
 */
class CalendarNotificationCategory extends NotificationCategory
{
    /**
     * @var string the category id
     */
    public $id = 'calendar';

    /**
     * Returns a human readable title of this  category
     */
    public function getTitle()
    {
        return Yii::t('CalendarModule.notifications_CalendarNotificationCategory', 'Calendar');
    }

    /**
     * Returns a group description
     */
    public function getDescription()
    {
        return Yii::t('CalendarModule.notifications_CalendarNotificationCategory', 'Receive Calendar related Notifications.');
    }
}