<?php

namespace humhub\modules\calendar\tests\codeception\unit\entry;

use calendar\CalendarUnitTest;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\content\models\Content;
use DateTime;
use DateInterval;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ActiveQueryContent;
use Yii;

class LegacyCalendarEntryTest extends CalendarUnitTest
{

    public function testLegacyAllDayEventWithTimezonetranslationSingleDaySpan()
    {
        Yii::$app->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->locale = 'de';

        $this->becomeUser('User2');
        $space1 = Space::findOne(['id' => 1]);
        $calendarForm = CalendarEntryForm::createEntry($space1, '2019-11-13 00:00:00', '2019-11-14 00:00:00');
        $calendarForm->entry->title = 'Legacy Event';
        $this->assertTrue($calendarForm->save());

        // Save legacy all day
        $calendarForm->entry->updateAttributes([
            'start_datetime' => '2019-11-12 23:00:00', // Should be 2019-11-13 00:00:00
            'end_datetime' => '2019-11-13 22:59:00', // Should be 2019-11-14 00:00:00
            'time_zone' => 'Europe/Bucharest',
            'all_day' => 1
        ]);

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('2019-11-13', $calendarForm->start_date);
        $this->assertEquals('2019-11-13', $calendarForm->end_date);
        $this->assertEquals('2019-11-13 00:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2019-11-14 00:00:00', $calendarForm->entry->end_datetime);
    }

    public function testLegacyAllDayEventWithTimezonetranslationMultiDaySpan()
    {
        Yii::$app->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->locale = 'de';

        $this->becomeUser('User2');
        $space1 = Space::findOne(['id' => 1]);
        $calendarForm = CalendarEntryForm::createEntry($space1, '2019-11-13 00:00:00', '2019-11-14 00:00:00');
        $calendarForm->entry->title = 'Legacy Event';
        $this->assertTrue($calendarForm->save());

        // Save legacy all day
        $calendarForm->entry->updateAttributes([
            'start_datetime' => '2019-11-12 23:00:00', // Should be 2019-11-13 00:00:00
            'end_datetime' => '2019-11-14 22:59:00', // Should be 2019-11-14 00:00:00
            'time_zone' => 'Europe/Bucharest',
            'all_day' => 1
        ]);

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('2019-11-13', $calendarForm->start_date);
        $this->assertEquals('2019-11-14', $calendarForm->end_date);
        $this->assertEquals('2019-11-13 00:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2019-11-15 00:00:00', $calendarForm->entry->end_datetime);
    }

    public function testLegacyMomentBeforeEventSingleDaySpan()
    {
        Yii::$app->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->locale = 'de';

        $this->becomeUser('User2');
        $space1 = Space::findOne(['id' => 1]);
        $calendarForm = CalendarEntryForm::createEntry($space1, '2019-11-13 00:00:00', '2019-11-14 00:00:00');
        $calendarForm->entry->title = 'Legacy Event';
        $this->assertTrue($calendarForm->save());

        // Save legacy all day
        $calendarForm->entry->updateAttributes([
            'start_datetime' => '2019-11-12 00:00:00', // Should be 2019-11-13 00:00:00
            'end_datetime' => '2019-11-12 23:59:00', // Should be 2019-11-14 00:00:00
            'all_day' => 1
        ]);

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('2019-11-12 00:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2019-11-13 00:00:00', $calendarForm->entry->end_datetime);
        $this->assertEquals('2019-11-12', $calendarForm->start_date);
        $this->assertEquals('2019-11-12', $calendarForm->end_date);
    }

    public function testLegacyMomentBeforeEventMultiDaySpan()
    {
        Yii::$app->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->locale = 'de';

        $this->becomeUser('User2');
        $space1 = Space::findOne(['id' => 1]);
        $calendarForm = CalendarEntryForm::createEntry($space1, '2019-11-13 00:00:00', '2019-11-14 00:00:00');
        $calendarForm->entry->title = 'Legacy Event';
        $this->assertTrue($calendarForm->save());

        // Save legacy all day
        $calendarForm->entry->updateAttributes([
            'start_datetime' => '2019-11-12 00:00:00', // Should be 2019-11-13 00:00:00
            'end_datetime' => '2019-11-13 23:59:00', // Should be 2019-11-14 00:00:00
            'all_day' => 1
        ]);

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('2019-11-12 00:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2019-11-14 00:00:00', $calendarForm->entry->end_datetime);
        $this->assertEquals('2019-11-12', $calendarForm->start_date);
        $this->assertEquals('2019-11-13', $calendarForm->end_date);
    }
}
