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
 * Time: 12:44
 */

namespace humhub\modules\calendar\interfaces;


class CalendarItemTypesEvent extends CalendarEvent
{
    private $result = [];

    public function addType($key, $options) {
        $this->result[$key] = $options;
    }

    public function hasType($key) {
        return array_key_exists($key, $this->result);
    }

    public function getTypes() {
        return $this->result;
    }
}