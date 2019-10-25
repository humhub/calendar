<?php


namespace calendar;


use DateInterval;
use DateTime;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\models\Content;
use tests\codeception\_support\HumHubDbTestCase;

class CalendarUnitTest extends HumHubDbTestCase
{
    protected function createEntry($from, $days, $title, $container = null, $visibility = Content::VISIBILITY_PUBLIC)
    {
        if (!$from) {
            $from = new DateTime();
        }

        if(is_int($days)) {
            $to = clone $from;
            $to->add(new DateInterval("P" . $days . "D"));
        } else {
            $to = $days;
        }

        $entry = new CalendarEntry();
        $entry->title = $title;
        $entry->start_datetime = Yii::$app->formatter->asDateTime($from, 'php:Y-m-d') . " 00:00:00";
        $entry->end_datetime = Yii::$app->formatter->asDateTime($to, 'php:Y-m-d') . " 23:59:59";
        $entry->content->visibility = $visibility;

        if($container) {
            $entry->content->container = $container;
        }

        $entry->save();

        return $entry;
    }
}