<?php

namespace humhub\modules\calendar\widgets;

use Yii;
use humhub\components\Widget;

/**
 * Description of FullCalendarWidget
 *
 * @author luke
 */
class FullCalendar extends Widget
{

    public $canWrite = true;
    public $loadUrl = "";
    public $createUrl = "";
    public $selectors = array();
    public $filters = array();

    public function run()
    {
        return $this->render('fullCalendar', [
                    'loadUrl' => $this->loadUrl,
                    'canWrite' => $this->canWrite,
                    'createUrl' => $this->createUrl,
                    'selectors' => $this->selectors,
                    'filters' => $this->filters
        ]);
    }

    public static function populate($calendarEntry)
    {
        $startTime = new \DateTime(Yii::$app->request->get('start_datetime', ''));
        $endTime = new \DateTime(Yii::$app->request->get('end_datetime', ''));

        $calendarEntry->start_datetime = Yii::$app->formatter->asDate($startTime);
        $calendarEntry->start_time = $startTime->format('H:i');

        // Fix FullCalendar EndTime
        if (\humhub\modules\calendar\Utils::isFullDaySpan($startTime, $endTime, true)) {
            // In Fullcalendar the EndTime is the moment AFTER the event
            $oneSecond = new \DateInterval("PT1S");
            $endTime->sub($oneSecond);

            $calendarEntry->all_day = 1;
        }

        $calendarEntry->end_time = $endTime->format('H:i');
        $calendarEntry->end_datetime = Yii::$app->formatter->asDate($endTime);
    }

}

?>
