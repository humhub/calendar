<?php

namespace humhub\modules\calendar\tests\codeception\unit\entry;

use calendar\CalendarUnitTest;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\content\models\Content;
use DateTime;
use DateInterval;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ActiveQueryContent;
use Yii;

class CalendarServiceTest extends CalendarUnitTest
{
    public $service;

    public function _before()
    {
        parent::_before();
        $this->service = new CalendarService();
    }

    public function testAllDayStartIncludedBoundary()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $searchDateStart = new DateTime('2019-12-30T00:00:00+01:00');
        $searchDateEnd = new DateTime('2020-02-10T00:00:00+01:00');

        // Entry from '2019-12-30 00:00:00' - '2020-12-31 00:00:00' should be included in search
        $entry1 = $this->createEntry(clone $searchDateStart, 1, 'e1', $s1);
        $entries = $this->service->getCalendarItems($searchDateStart, $searchDateEnd);
        $this->assertCount(1, $entries);
        $this->assertEquals($entry1->id, $entries[0]->id);
    }

    public function testAllDayStartNotIncludedBoundary()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $searchDateStart = new DateTime('2019-12-30T00:00:00+01:00');
        $searchDateEnd = new DateTime('2020-02-10T00:00:00+01:00');


        $entryStart = clone $searchDateStart;
        $entryStart->modify('-1 day');

        // Entry from '2019-12-29 00:00:00' - '2020-12-30 00:00:00' should not be included in search
        $this->createEntry($entryStart , 1, 'e1', $s1);
        $entries = $this->service->getCalendarItems($searchDateStart, $searchDateEnd);
        $this->assertCount(0, $entries);
    }

    public function testAllDayEndNotIncludedBoundary()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $searchDateStart = new DateTime('2019-12-30T00:00:00+01:00');
        $searchDateEnd = new DateTime('2020-02-10T00:00:00+01:00');

        // Entry from '2019-02-10 00:00:00' - '2020-02-11 00:00:00' should not be included in search
        $entry1 = $this->createEntry(clone $searchDateEnd, 1, 'e1', $s1);
        $entries = $this->service->getCalendarItems($searchDateStart, $searchDateEnd);
        $this->assertCount(0, $entries);
    }

    public function testAllDayEndIncludedBoundary()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $searchDateStart = new DateTime('2019-12-30T00:00:00+01:00');
        $searchDateEnd = new DateTime('2020-02-10T00:00:00+01:00');

        $entryStart = (clone $searchDateEnd);
        $entryStart->modify('-1 day');

        // Entry from '2019-02-10 00:00:00' - '2020-02-11 00:00:00' should not be included in search
        $entry1 = $this->createEntry($entryStart, 1, 'e1', $s1);
        $entries = $this->service->getCalendarItems($searchDateStart, $searchDateEnd);
        $this->assertCount(1, $entries);
        $this->assertEquals($entry1->id, $entries[0]->id);
    }

    public function testAllDayStartIncludedBoundaryTZ()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $searchDateStart = new DateTime('2019-12-30T00:00:00+02:00');
        $searchDateEnd = new DateTime('2020-02-10T00:00:00+02:00');

        // Entry from '2019-12-30 00:00:00' - '2020-12-31 00:00:00' should be included in search
        $entry1 = $this->createEntry(clone $searchDateStart, 1, 'e1', $s1);
        $entries = $this->service->getCalendarItems($searchDateStart, $searchDateEnd);
        $this->assertCount(1, $entries);
        $this->assertEquals($entry1->id, $entries[0]->id);
    }

    public function testAllDayStartNotIncludedBoundaryTZ()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $searchDateStart = new DateTime('2019-12-30T00:00:00+02:00');
        $searchDateEnd = new DateTime('2020-02-10T00:00:00+02:00');


        $entryStart = clone $searchDateStart;
        $entryStart->modify('-1 day');

        // Entry from '2019-12-29 00:00:00' - '2020-12-30 00:00:00' should not be included in search
        $this->createEntry($entryStart , 1, 'e1', $s1);
        $entries = $this->service->getCalendarItems($searchDateStart, $searchDateEnd);
        $this->assertCount(0, $entries);
    }

    public function testAllDayEndNotIncludedBoundaryTZ()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $searchDateStart = new DateTime('2019-12-30T00:00:00+02:00');
        $searchDateEnd = new DateTime('2020-02-10T00:00:00+02:00');

        // Entry from '2019-02-10 00:00:00' - '2020-02-11 00:00:00' should not be included in search
        $entry1 = $this->createEntry(clone $searchDateEnd, 1, 'e1', $s1);
        $entries = $this->service->getCalendarItems($searchDateStart, $searchDateEnd);
        $this->assertCount(0, $entries);
    }

    public function testAllDayEndIncludedBoundaryTZ()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $searchDateStart = new DateTime('2019-12-30T00:00:00+02:00');
        $searchDateEnd = new DateTime('2020-02-10T00:00:00+02:00');

        $entryStart = clone $searchDateEnd;
        $entryStart->modify('-1 day');

        // Entry from '2019-02-10 00:00:00' - '2020-02-11 00:00:00' should not be included in search
        $entry1 = $this->createEntry($entryStart, 1, 'e1', $s1);
        $entries = $this->service->getCalendarItems($searchDateStart, $searchDateEnd);
        $this->assertCount(1, $entries);
        $this->assertEquals($entry1->id, $entries[0]->id);
    }

    /**
     * [] = Event {} = Search
     * 
     * [------{]------}
     * 
     * @throws \Exception
     */
    public function testNonAllDayStartIncludedBoundary()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $searchDateStart = new DateTime('2019-12-29T00:00:00+01:00');
        $searchDateEnd = new DateTime('2019-12-30T00:00:00+01:00');

        // Entry from '2019-12-29T00:00:00' - '2019-12-29T00:00:00' should be included in search
        $entryStart = new DateTime('2019-12-28T12:00:00');
        $entryEnd = new DateTime('2019-12-29T00:01:00');
        
        $entry1 = $this->createEntry($entryStart, $entryEnd, 'e1', $s1);
        $entries = $this->service->getCalendarItems($searchDateStart, $searchDateEnd);
        $this->assertCount(1, $entries);
        $this->assertEquals($entry1->id, $entries[0]->id);
    }

    /**
     * [] = Event {} = Search
     *
     * [------{]------}
     *
     * @throws \Exception
     */
    public function testNonAllDayStartNotIncludedBoundary()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $searchDateStart = new DateTime('2019-12-29T00:00:00+01:00');
        $searchDateEnd = new DateTime('2019-12-30T00:00:00+01:00');

        // Entry from '2019-12-29T00:00:00' - '2019-12-29T00:00:00' should be included in search
        $entryStart = new DateTime('2019-12-28T12:00:00');
        $entryEnd = new DateTime('2019-12-29T00:00:00');

        $this->createEntry($entryStart, $entryEnd, 'e1', $s1);
        $entries = $this->service->getCalendarItems($searchDateStart, $searchDateEnd);
        $this->assertCount(0, $entries);
    }

    /**
     *
     * [] = Event {} = Search
     *
     *        [------]{------}
     *
     * @throws \Exception
    */
    public function testNonAllDayStartNotIncludedBoundaryTZ()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->identity->updateAttributes(['time_zone' => 'Europe/Berlin']); // UTC+1
        Yii::$app->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->locale = 'de';

        $s1 = Space::findOne(['id' => 1]);

        $searchDateStart = new DateTime('2019-12-29T00:00:00+00:00'); // UTC+0 Europe/London
        $searchDateEnd = new DateTime('2019-12-30T00:00:00+00:00');

        // Berlin date from '2019-12-29T00:00:00' - '2019-12-29T00:00:00' UTC+1 should not be included in search
        $entryStart = new DateTime('2019-12-29 00:00:00');
        $entryEnd = new DateTime('2019-12-29 00:30:00');

        $entry = $this->createEntry($entryStart, $entryEnd, 'e1', $s1);
        $this->assertEquals('Europe/Berlin', $entry->getTimezone());
        $entries = $this->service->getCalendarItems($searchDateStart, $searchDateEnd);
        $this->assertCount(0, $entries);
    }
}
