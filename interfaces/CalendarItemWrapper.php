<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 14.09.2017
 * Time: 17:16
 */

namespace humhub\modules\calendar\interfaces;


use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\event\CalendarTypeIF;
use humhub\modules\calendar\interfaces\event\legacy\CalendarEventIFWrapper;
use humhub\modules\calendar\interfaces\fullcalendar\FullCalendarEventIF;
use humhub\widgets\Label;
use Yii;
use \DateTime;
use yii\base\Model;

/**
 * Class CalendarEventIFWrapper
 * @package humhub\modules\calendar\interfaces
 * @deprecated Used for deprecated array based calendar interface (prior to v1.0.0)
 */
class CalendarItemWrapper extends CalendarEventIFWrapper
{
}