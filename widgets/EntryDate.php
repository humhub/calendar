<?php

namespace humhub\modules\calendar\widgets;

use DateTime;
use humhub\components\Widget;

/**
 * Description of CalendarEntryDateWidget
 *
 * @author luke
 */
class EntryDate extends Widget
{

    public $calendarEntry;

    public function run()
    {
        $start = new DateTime($this->calendarEntry->start_datetime);
        $end = new DateTime($this->calendarEntry->end_datetime);

        return $this->render('displayDate', array(
                    'calendarEntry' => $this->calendarEntry,
                    'durationDays' => $this->calendarEntry->GetDurationDays(),
                    'start' => $start,
                    'end' => $end,
        ));
    }

}
