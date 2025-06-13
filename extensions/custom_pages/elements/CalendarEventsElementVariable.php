<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\extensions\custom_pages\elements;

use humhub\modules\custom_pages\modules\template\elements\BaseElementVariableIterator;

class CalendarEventsElementVariable extends BaseElementVariableIterator
{
    public function __construct(CalendarEventsElement $elementContent)
    {
        parent::__construct($elementContent);

        foreach ($elementContent->getItems() as $calendarEntry) {
            $this->items[] = CalendarEntryElementVariable::instance($elementContent)->setRecord($calendarEntry);
        }
    }
}
