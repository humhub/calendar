<?php

namespace module\calendar\widgets;

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
        return $this->render('displayDate', array(
                    'calendarEntry' => $this->calendarEntry
        ));
    }

}
