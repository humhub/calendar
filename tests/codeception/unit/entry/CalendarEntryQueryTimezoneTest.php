<?php

namespace humhub\modules\calendar\tests\codeception\unit\entry;

use calendar\CalendarUnitTest;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\content\models\Content;
use DateTime;
use DateInterval;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ActiveQueryContent;
use Yii;

class CalendarEntryQueryTimezoneTest extends CalendarUnitTest
{
    /**
     * @throws \Throwable
     */
    public function testTimeZoneStartIncluded()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->identity->updateAttributes(['time_zone' => 'Europe/London']); // UTC+1
        Yii::$app->timeZone = 'Europe/London';
        Yii::$app->formatter->timeZone = 'Europe/London';
        Yii::$app->formatter->locale = 'de';

        $start = new DateTime('2019-01-15 00:00:00', new \DateTimeZone('Europe/London'));
        $end = new DateTime('2019-01-16 00:00:00', new \DateTimeZone('Europe/London'));

        $entry = $this->createEntry($start, $end, 'Berlin Date', Space::findOne(['id' => 1]));
        $this->assertEquals('Europe/London', $entry->getTimezone());
        $this->assertEquals(0, $entry->all_day);

        $resultBerlin = CalendarEntryQuery::find()
            ->from(new DateTime('2019-01-16 00:00:00', new \DateTimeZone('Europe/Berlin')))
            ->to((new DateTime('2019-01-17 00:00:00', new \DateTimeZone('Europe/Berlin'))))
            ->openRange(true)->all();

        $this->assertEquals(1, count($resultBerlin));
        $this->assertEquals('Berlin Date', $resultBerlin[0]->title);

        $resultLondon = CalendarEntryQuery::find()
            ->from(new DateTime('2019-01-16 00:00:00', new \DateTimeZone('Europe/London')))
            ->to((new DateTime('2019-01-17 00:00:00', new \DateTimeZone('Europe/London'))))
            ->openRange(true)->all();

        $this->assertEquals(0, count($resultLondon));
    }

    /**
     * @throws \Throwable
     */
    public function testTimeZoneEndIncluded()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->identity->updateAttributes(['time_zone' => 'Europe/Berlin']); // UTC+1
        Yii::$app->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->locale = 'de';

        $start = new DateTime('2019-01-15 00:00:00', new \DateTimeZone('Europe/Berlin'));
        $end = new DateTime('2019-01-16 00:00:00', new \DateTimeZone('Europe/Berlin'));

        $entry = $this->createEntry($start, $end, 'Berlin Date', Space::findOne(['id' => 1]));
        $this->assertEquals('Europe/Berlin', $entry->getTimezone());
        $this->assertEquals(0, $entry->all_day);

        $resultLondon = CalendarEntryQuery::find()
            ->from(new DateTime('2019-01-14 00:00:00', new \DateTimeZone('Europe/London')))
            ->to((new DateTime('2019-01-14 23:59:59', new \DateTimeZone('Europe/London'))))
            ->openRange(true)->all();

        $this->assertEquals(1, count($resultLondon));
        $this->assertEquals('Berlin Date', $resultLondon[0]->title);

        $resultBerlin = CalendarEntryQuery::find()
            ->from(new DateTime('2019-01-14 00:00:00', new \DateTimeZone('Europe/Berlin')))
            ->to((new DateTime('2019-01-14 23:59:59', new \DateTimeZone('Europe/Berlin'))))
            ->openRange(true)->all();

        $this->assertEquals(0, count($resultBerlin));
    }
}
