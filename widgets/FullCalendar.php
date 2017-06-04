<?php

namespace humhub\modules\calendar\widgets;

use Yii;
use yii\helpers\Url;

/**
 * Description of FullCalendarWidget
 *
 * @author luke
 */
class FullCalendar extends \humhub\widgets\JsWidget
{
    public $jsWidget = 'calendar.Calendar';
    public $id = 'calendar';
    public $init = true;
    public $canWrite = true;
    public $loadUrl;
    public $dropUrl;
    public $editUrl;
    public $selectors = [];
    public $filters = [];
    public $contentContainer;
    public $enabled = true;

    public function init()
    {
        \humhub\modules\calendar\assets\Assets::register($this->getView());

        if(Yii::$app->user->isGuest) {
            parent::init();
            return;
        }

        if(!$this->contentContainer) {
            $this->contentContainer = Yii::$app->user->getIdentity();
        }

        // Used by the global calendar if the module is not enabled for the given user.
        if($this->contentContainer && !$this->contentContainer->isModuleEnabled('calendar')) {
            $this->enabled = false;
        }


        $this->editUrl = $this->contentContainer->createUrl('/calendar/entry/edit', ['cal' => true]);
        $this->dropUrl = $this->contentContainer->createUrl('/calendar/entry/edit-ajax');
        parent::init();
    }
    
    public function getData()
    {
        return [
            'load-url' => $this->loadUrl,
            'edit-url' => $this->editUrl,
            'drop-url' => $this->dropUrl,
            'enable-url' => Url::to(['/calendar/global/enable']),
            'can-write' => $this->canWrite,
            'editable' => $this->canWrite,
            'selectable' => $this->canWrite,
            'selectHelper' => $this->canWrite,
            'selectors' => $this->selectors,
            'filters' => $this->filters,
            'timezone' => date_default_timezone_get(),
            'lang' => Yii::$app->language,
            'enabled' => $this->enabled
        ];
    }

    public static function populate($calendarEntry, $timeZone = '')
    {
        if ($timeZone == '') {
            $timeZone = Yii::$app->formatter->timeZone;
        }

        $start = Yii::$app->request->get('start', Yii::$app->request->post('start'));
        $end = Yii::$app->request->get('end', Yii::$app->request->post('end'));

        // Get given start & end datetime
        if($start) {
            $startTime = new \DateTime($start, new \DateTimeZone($timeZone));
        }

        if($end) {
            $endTime = new \DateTime($end, new \DateTimeZone($timeZone));
        }

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
