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
 * Time: 17:17
 */

namespace humhub\modules\calendar\interfaces;


use DateTime;

class CalendarItemsEvent extends CalendarEvent
{
    /**
     * @var array
     */
    public $filters;

    /**
     * @var DateTime
     */
    public $start;

    /**
     * @var DateTime
     */
    public $end;

    /**
     * @var integer result limit
     */
    public $limit;

    /**
     * @var boolean whether or not to expand recurring events
     */
    public $expand;

    /**
     * @var []
     */
    public $items = [];

    /**
     * @param $item array
     */
    public function addItems($itemType, $items)
    {
        $items = is_array($items) ? $items : [$items];
        if(!isset($this->items[$itemType])) {
            $this->items[$itemType] = $items;
        } else {
            $this->items[$itemType] = array_merge($this->items[$itemType], $items);
        }
    }

    /**
     * @inheritdoc
     */
    public function getItems()
    {
        return $this->items;
    }

}