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

    public static function populate($calendarEntry, $timeZone = '')
    {
        if ($timeZone == '') {
            $timeZone = Yii::$app->formatter->timeZone;
        }

        // Get given start & end datetime
        $startTime = new \DateTime(Yii::$app->request->get('start_datetime', ''), new \DateTimeZone($timeZone));
        $endTime = new \DateTime(Yii::$app->request->get('end_datetime', ''), new \DateTimeZone($timeZone));

        // Remember current (user) timeZone - and switch to system timezone
        $userTimeZone = Yii::$app->formatter->timeZone;
        Yii::$app->formatter->timeZone = Yii::$app->timeZone;

        $calendarEntry->start_datetime = Yii::$app->formatter->asDateTime($startTime, 'php:Y-m-d H:i:s');
        $calendarEntry->start_time = $startTime->format('H:i');

        // Fix FullCalendar EndTime
        if (\humhub\modules\calendar\Utils::isFullDaySpan($startTime, $endTime, true)) {
            // In Fullcalendar the EndTime is the moment AFTER the event
            $oneSecond = new \DateInterval("PT1S");
            $endTime->sub($oneSecond);

            $calendarEntry->all_day = 1;
        }

        $calendarEntry->end_time = $endTime->format('H:i');
        $calendarEntry->end_datetime = Yii::$app->formatter->asDateTime($endTime, 'php:Y-m-d H:i:s');

        // Switch back to user time zone
        Yii::$app->formatter->timeZone = $userTimeZone;
    }

}

?>
