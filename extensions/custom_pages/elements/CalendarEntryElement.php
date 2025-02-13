<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\extensions\custom_pages\elements;

use humhub\libs\Html;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordElement;
use Yii;

/**
 * Class to manage content record of the Calendar event
 *
 * @property-read CalendarEntry|null $record
 */
class CalendarEntryElement extends BaseContentRecordElement
{
    protected const RECORD_CLASS = CalendarEntry::class;

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Yii::t('CalendarModule.template', 'Calendar event');
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('CalendarModule.template', 'Select calendar event'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function render($options = [])
    {
        $result = Html::encode($this->record->title);

        if ($this->isEditMode($options)) {
            return $this->wrap('span', $result, $options);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getFormView(): string
    {
        return '@calendar/extensions/custom_pages/elements/views/calendar';
    }
}
