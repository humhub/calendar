<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\extensions\custom_pages\elements;

use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordElementVariable;
use humhub\modules\custom_pages\modules\template\elements\BaseRecordElementVariable;
use yii\db\ActiveRecord;

class CalendarEntryElementVariable extends BaseContentRecordElementVariable
{
    public string $title;
    public string $description;
    public string $url;
    public string $color;
    public string $startDateTime;
    public string $endDateTime;
    public string $location;

    public function setRecord(?ActiveRecord $record): BaseRecordElementVariable
    {
        if ($record instanceof CalendarEntry) {
            $this->title = $record->title ?? '';
            $this->description = $record->description ?? '';
            $this->url = $record->getUrl();
            $this->color = $record->color ?? '';
            $this->startDateTime = $record->start_datetime ?? '';
            $this->endDateTime = $record->end_datetime ?? '';
            $this->location = $record->location ?? '';

            // A dynamically expanded recurrence instance (see CalendarEventsElement)
            // which is not persisted as its own `calendar_entry`/Content record has no
            // Content of its own - no guid, no own created/updated timestamps, etc. -
            // see humhub/calendar#712. Rather than forcing a database write for every
            // occurrence just to satisfy BaseContentRecordElementVariable, we populate
            // those generic Content-based fields from the recurrence root instead,
            // since they conceptually describe the recurring series as a whole.
            if ($record->isNewRecord && RecurrenceHelper::isRecurrentInstance($record)) {
                return $this->setVirtualInstanceContent($record);
            }
        }

        return parent::setRecord($record);
    }

    /**
     * @param CalendarEntry $record a not yet persisted recurrence instance
     * @return BaseRecordElementVariable
     */
    protected function setVirtualInstanceContent(CalendarEntry $record): BaseRecordElementVariable
    {
        // $this->url was already set above to the occurrence's own URL (e.g.
        // .../calendar/entry/view-recurrence?...); parent::setRecord() below would
        // otherwise overwrite it with the recurrence root's own URL.
        $occurrenceUrl = $this->url;

        // CalendarEventsElement::filterResolvableRoots() already guarantees the root
        // of a virtual instance is resolvable before it ever reaches this class.
        $root = $record->getRecurrenceRoot();
        parent::setRecord($root);

        $this->url = $occurrenceUrl;

        // .author/.updater/.container (see BaseContentRecordElementVariable) resolve
        // against this record - the recurrence root (always persisted) is correct
        // here, since e.g. the container/space of a recurring series is the same for
        // every occurrence.
        $this->record = $root;

        return $this;
    }
}
