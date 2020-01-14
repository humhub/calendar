<?php


namespace humhub\modules\calendar\tests\codeception\unit\entry;


use calendar\CalendarUnitTest;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\space\models\Space;
use Yii;

class CalendarFormTimezoneTest extends CalendarUnitTest
{
    public function _before()
    {
        parent::_before();

        $this->becomeUser('User2');
        Yii::$app->user->identity->updateAttributes(['time_zone' => 'Europe/Berlin']);
        Yii::$app->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->locale = 'de';
    }

    public function testCreateNonAllDayWithDifferentUserTZ()
    {
        Yii::$app->user->identity->updateAttributes(['time_zone' => 'Europe/Bucharest']);

        $calendarForm = CalendarEntryForm::createEntry(Space::findOne(['id' => 1]), '2019-11-13 13:00:00', '2019-11-13 15:00:00');
        $entry = $calendarForm->entry;
        $this->assertFalse($calendarForm->entry->isAllDay());
        $this->assertEquals(1, $calendarForm->entry->allow_maybe);
        $this->assertEquals(1, $calendarForm->entry->allow_decline);
        $this->assertEquals(CalendarEntryParticipation::PARTICIPATION_MODE_ALL, $calendarForm->entry->participation_mode);
        $this->assertEquals('Europe/Bucharest', $entry->time_zone);
        $this->assertEquals('Europe/Bucharest', $calendarForm->timeZone);
        $this->assertEquals('2019-11-13 12:00:00', $entry->start_datetime);
        $this->assertEquals('2019-11-13 14:00:00', $entry->end_datetime);
    }

    public function testTimeZone()
    {
        // System TZ = Europe/Berlin -> UTC + 2
        $this->assertEquals('Europe/Berlin', CalendarUtils::getSystemTimeZone(true));

        $this->becomeUser('Admin');

        // Set User TZ to = Europe/London -> UTC + 1
        Yii::$app->user->getIdentity()->time_zone = 'Europe/London';

        $calendarForm = CalendarEntryForm::createEntry( Space::findOne(['id' => 1]));

        $this->assertTrue($calendarForm->load([
            'CalendarEntry' => [
                'all_day' => '0',
                'title' => 'Test title',
                'description' => 'TestDescription',
                'participation_mode' => 2
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'start_date' => '6/27/17',
                'start_time' => '12:00 PM',
                'end_date' => '6/28/17',
                'end_time' => '01:00 PM'
            ]
        ]));

        $this->assertTrue($calendarForm->save());

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);

        Yii::$app->user->getIdentity()->time_zone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';

        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('2017-06-27', $calendarForm->start_date);
        $this->assertEquals('12:00', $calendarForm->start_time);
        $this->assertEquals('2017-06-28', $calendarForm->end_date);
        $this->assertEquals('13:00', $calendarForm->end_time);

        $this->assertEquals('2017-06-27 13:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2017-06-28 14:00:00', $calendarForm->entry->end_datetime);


        // Change timezone of event to Europe/Sofia -> UTC +3
        $this->assertTrue($calendarForm->load([
            'CalendarEntry' => [
                'all_day' => '0'
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'timeZone' => 'Europe/Sofia',
                'start_date' => '6/27/17',
                'start_time' => '12:00 PM',
                'end_date' => '6/28/17',
                'end_time' => '01:00 PM'
            ]
        ]));

        $this->assertTrue($calendarForm->save());

        // Reload form
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);

        $this->assertEquals('2017-06-27', $calendarForm->start_date);
        $this->assertEquals('12:00', $calendarForm->start_time);
        $this->assertEquals('2017-06-28', $calendarForm->end_date);
        $this->assertEquals('13:00', $calendarForm->end_time);
        $this->assertEquals('Europe/Sofia', $calendarForm->timeZone);

        $this->assertEquals('Europe/Sofia', $calendarForm->entry->time_zone);
        $this->assertEquals('2017-06-27 11:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2017-06-28 12:00:00', $calendarForm->entry->end_datetime);
    }

    public function testAllDayTimeZone()
    {
        // System TZ = Europe/Berlin -> UTC + 2
        $this->assertEquals('Europe/Berlin', CalendarUtils::getSystemTimeZone(true));

        $this->becomeUser('Admin');

        // Set User TZ to = Europe/London -> UTC + 1
        Yii::$app->user->getIdentity()->time_zone = 'Europe/London';

        $space1 = Space::findOne(['id' => 1]);

        $calendarForm = CalendarEntryForm::createEntry($space1);
        $this->assertEquals('Europe/London', $calendarForm->timeZone);

        $this->assertTrue($calendarForm->load([
            'CalendarEntry' => [
                'all_day' => '1',
                'title' => 'Test title',
                'description' => 'TestDescription',
                'participation_mode' => 2
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'start_date' => '6/27/17',
                'end_date' => '6/27/17',
            ]
        ]));

        $this->assertTrue($calendarForm->save());

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('Europe/London', $calendarForm->timeZone);
        $this->assertEquals('2017-06-27', $calendarForm->start_date);
        $this->assertEquals('2017-06-27', $calendarForm->end_date);

        // Timezone is ignored for all day events
        $this->assertEquals('2017-06-27 00:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2017-06-28 00:00:00', $calendarForm->entry->end_datetime);
    }

    public function testCreateAllDayTimeZone()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->getIdentity()->time_zone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';

        $space1 = Space::findOne(['id' => 1]);

        $calendarForm = CalendarEntryForm::createEntry($space1, '2017-08-16 00:00:00', '2017-08-17 00:00:00');
        $this->assertEquals('Europe/Berlin', $calendarForm->timeZone);
        // This is how a full day is given in fullcalendar
        $this->assertEquals('Europe/Berlin', $calendarForm->timeZone);
        $this->assertEquals('2017-08-16', $calendarForm->start_date);
        $this->assertEquals('2017-08-16', $calendarForm->end_date);

        $this->assertTrue($calendarForm->load([
            'CalendarEntry' => [
                'all_day' => '1',
                'title' => 'Test title',
                'description' => 'TestDescription',
                'participation_mode' => 2
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'start_date' => '8/16/17',
                'end_date' => '8/16/17',
            ]
        ]));

        $this->assertTrue($calendarForm->save());

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('Europe/Berlin', $calendarForm->timeZone);
        $this->assertEquals('2017-08-16', $calendarForm->start_date);
        $this->assertEquals('2017-08-16 00:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2017-08-16', $calendarForm->end_date);
        $this->assertEquals('2017-08-17 00:00:00', $calendarForm->entry->end_datetime);
    }
}