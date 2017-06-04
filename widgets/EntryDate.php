<?php

namespace humhub\modules\calendar\widgets;

use DateTime;
use humhub\components\Widget;

/**
 * Description of CalendarEntryDate
 *
 * @author luke
 */
class EntryDate extends Widget
{

    /**
     * @var \humhub\modules\calendar\models\CalendarEntry 
     */
    public $entry;

    /**
     * @inerhitdoc
     */
    public function run()
    {
        $start = new DateTime($this->entry->start_datetime);
        $end = new DateTime($this->entry->end_datetime);

        return $this->render('displayDate', [
                    'entry' => $this->entry,
                    'durationDays' => $this->entry->getDurationDays(),
                    'start' => $start,
                    'end' => $end,
        ]);
    }

}
