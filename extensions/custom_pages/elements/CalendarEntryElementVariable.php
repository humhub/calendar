<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\extensions\custom_pages\elements;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\custom_pages\modules\template\elements\BaseRecordElementVariable;
use yii\db\ActiveRecord;

class CalendarEntryElementVariable extends BaseRecordElementVariable
{
    public string $title;
    public string $description;
    public string $url;
    public string $color;
    public string $start_datetime;
    public string $end_datetime;
    public string $location;

    public function setRecord(?ActiveRecord $record): BaseRecordElementVariable
    {
        if ($record instanceof CalendarEntry) {
            $this->title = $record->title;
            $this->description = $record->description;
            $this->url = $record->getUrl();
            $this->color = $record->color;
            $this->start_datetime = $record->start_datetime;
            $this->end_datetime = $record->end_datetime;
            $this->location = $record->location;
        }

        return parent::setRecord($record);
    }
}
