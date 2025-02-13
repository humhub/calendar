<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\ui\form\widgets\BasePicker;
use Yii;

class CalendarEntryPicker extends BasePicker
{
    /**
     * @inheritdoc
     */
    public $minInput = 2;

    /**
     * @inheritdoc
     */
    public $defaultRoute = '/calendar/global/picker-search';

    /**
     * @inheritdoc
     */
    public $itemClass = CalendarEntry::class;

    /**
     * @inheritdoc
     */
    protected function getData()
    {
        $result = parent::getData();

        if ($this->maxSelection) {
            $result['maximum-selected'] = Yii::t('CalendarModule.base', 'This field only allows a maximum 1 calendar entry.');
        }

        return $result;
    }

    /**
     * @inheritdoc
     * @param CalendarEntry $item
     */
    protected function getItemText($item)
    {
        return $item->title;
    }

    /**
     * @inheritdoc
     */
    protected function getItemImage($item)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function getAttributes()
    {
        return array_merge(parent::getAttributes(), [
            'data-tags' => 'false',
        ]);
    }
}
