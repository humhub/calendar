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
 * Time: 12:40
 */

namespace humhub\modules\calendar\interfaces;


use yii\base\Event;

class CalendarEvent extends Event
{
    public $contentContainer;
}