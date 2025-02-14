<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\extensions\custom_pages\elements;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordsElement;
use Yii;

/**
 * Class to manage content records of the elements with Calendar events list
 */
class CalendarEventsElement extends BaseContentRecordsElement
{
    public const RECORD_CLASS = CalendarEntry::class;
    public string $subFormView = '@calendar/extensions/custom_pages/elements/views/calendars';

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Yii::t('CalendarModule.base', 'Calendar events');
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'static' => Yii::t('CalendarModule.base', 'Select calendars'),
        ]);
    }
}
