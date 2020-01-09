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

namespace humhub\modules\calendar\interfaces\event;


use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\base\Event;

class CalendarItemTypesEvent extends Event
{
    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    private $result = [];

    /**
     * @param $key string|CalendarTypeIF
     * @param $options CalendarTypeIF
     */
    public function addType($key, $options) {
        if($key instanceof CalendarTypeIF) {
            $this->result[$key->getKey()] = $key;
        } else {
            $this->result[$key] = $options;
        }
    }

    public function hasType($key) {
        return array_key_exists($key, $this->result);
    }

    public function getTypes() {
        return $this->result;
    }
}