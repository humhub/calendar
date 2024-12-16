<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\content\components\ContentContainerActiveRecord;

class CalendarTypeLegend extends Widget
{
    public ?ContentContainerActiveRecord $contentContainer = null;

    public function run()
    {
        $calendarTypes = $this->getCalendarTypes();

        if (empty($calendarTypes)) {
            return '';
        }

        return $this->render('calendarTypeLegend', [
            'calendarTypes' => $calendarTypes,
        ]);
    }

    /**
     * @return CalendarEntryType[]
     */
    public function getCalendarTypes(): array
    {
        $query = $this->contentContainer
            ? CalendarEntryType::findByContainer($this->contentContainer, true)
            : CalendarEntryType::find();

        return $query->readable()->all();
    }
}
