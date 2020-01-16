<?php


namespace humhub\modules\calendar\models\recurrence;


use humhub\modules\calendar\interfaces\recurrence\AbstractRecurrenceQuery;
use humhub\modules\calendar\models\CalendarEntry;

class CalendarEntryRecurrenceQuery extends AbstractRecurrenceQuery
{
    public static $recordClass = CalendarEntry::class;
}