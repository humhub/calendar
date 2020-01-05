<?php


namespace calendar;


use humhub\modules\calendar\Events;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\user\models\User;
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
        Yii::$app->getModule('calendar')->maxReminder = 100;
    }

    protected function createReminder($unit, $value, $model = null, User $user = null)
    {
        $reminder = new CalendarReminder([
            'unit' => $unit,
            'value' => $value
        ]);

        if ($model instanceof ContentContainerActiveRecord) {
            $reminder->contentcontainer_id = $model->contentcontainer_id;
        }

        if ($model instanceof ContentActiveRecord) {
            $reminder->content_id = $model->id;
        }

        if($user) {
            $reminder->contentcontainer_id = $user->contentcontainer_id;
        }

        $this->assertTrue($reminder->save());

        return $reminder;
    }

    protected function createEntry($from, $days, $title, $container = null, $visibility = Content::VISIBILITY_PUBLIC)
    {
        if (!$from) {
            $from = new DateTime();
        }

        if (is_int($days)) {
            $fullDay = true;
            $to = clone $from;
            $to->add(new DateInterval("P" . $days . "D"));
        } else if($days instanceof DateTime) {
            $fullDay = false;
            $to = $days;
        } else if($days instanceof DateInterval) {
            $fullDay = false;
            $to = clone $from;
            $to->add($days);
        } else if(!$days) {
            $fullDay = false;
            $to = clone $from;
            $to->add(new DateInterval('PT1H'));
        }

        $entry = new CalendarEntry();
        $entry->title = $title;

        if($fullDay) {
            $entry->all_day = 1;
            $from->setTime(0,0,0);
            $to->setTime(23,59,59);
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