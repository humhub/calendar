<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\content\helpers\ContentContainerHelper;
use humhub\modules\content\widgets\ContentTagPicker;

/**
 * This FilterType is used to filter calendar events by types.
 */
class FilterType extends ContentTagPicker
{
    /**
     * @inheritdoc
     */
    public $itemClass = CalendarEntryType::class;

    /**
     * @inheritdoc
     */
    public $minInput = 2;

    /**
     * @inheritdoc
     */
    public $showDefaults = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->contentContainer = $this->contentContainer ?: ContentContainerHelper::getCurrent();

        $this->url = Url::toFindFilterTypes($this->contentContainer);

        parent::init();
    }

    /**
     * @inheritdoc
     * @param CalendarEntryType $item
     */
    protected function getItemImage($item)
    {
        return $item->color;
    }
}
