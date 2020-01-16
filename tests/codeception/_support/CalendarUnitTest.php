<?php


namespace calendar;


use humhub\modules\calendar\Events;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use Yii;
use DateInterval;
use DateTime;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\models\Content;
use tests\codeception\_support\HumHubDbTestCase;

class CalendarUnitTest extends HumHubDbTestCase
{
    public function _before()
    {
        Yii::$app->timeZone = 'Europe/Berlin';
        parent::_before();
        Events::registerAutoloader();
        CalendarReminder::flushDefautlts();
        CalendarService::flushCache();
        CalendarUtils::flush();
        Yii::$app->getModule('calendar')->maxReminder = 100;
    }

    protected function createEntry($from, $days, $title, $container = null, $visibility = Content::VISIBILITY_PUBLIC)
    {
        if (!$from) {
            $from = new DateTime();
        }

        $fullDay = false;
        if(is_string($days)) {
            $to = clone $from;
            $to->add(new DateInterval($days));
            $fullDay = CalendarUtils::isAllDay($from, $to);
        }else if (is_int($days)) {
            $fullDay = true;
            $to = clone $from;
            $to->setTime(0,0,0)->add(new DateInterval("P" . $days . "D"));
        } else if($days instanceof DateTime) {
            $to = clone $days;
        } else if($days instanceof DateInterval) {
            $to = clone $from;
            $to->add($days);
        } else if(!$days) {
            $to = clone $from;
            $to->add(new DateInterval('PT1H'));
        }

        $entry = new CalendarEntry();
        $entry->title = $title;

        if($fullDay) {
            $entry->all_day = 1;
            $from->setTime(0,0,0);
            $to->modify('+1 hour')->setTime(0,0,0);
        } else {
            $entry->all_day = 0;
        }


        $entry->start_datetime = $from->format(CalendarUtils::DB_DATE_FORMAT);
        $entry->end_datetime = $to->format(CalendarUtils::DB_DATE_FORMAT);
        $entry->content->visibility = $visibility;

        if ($container) {
            $entry->content->container = $container;
        }

        $this->assertTrue($entry->save());
        return $entry;
    }
}