<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\extensions\custom_pages\elements;

use humhub\helpers\Html;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordElement;
use humhub\modules\custom_pages\modules\template\elements\BaseElementVariable;
use Yii;

/**
 * Class to manage content record of the Calendar event
 *
 * @property-read CalendarEntry|null $record
 */
class CalendarEntryElement extends BaseContentRecordElement implements \Stringable
{
    protected const RECORD_CLASS = CalendarEntry::class;

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Yii::t('CalendarModule.base', 'Calendar event');
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'contentId' => Yii::t('CalendarModule.base', 'Calendar event content ID'),
        ];
    }

    public function __toString(): string
    {
        return (string) Html::encode($this->record?->title);
    }

    /**
     * @inheritdoc
     */
    public function getTemplateVariable(): BaseElementVariable
    {
        return CalendarEntryElementVariable::instance($this)
            ->setRecord($this->getRecord());
    }
}
