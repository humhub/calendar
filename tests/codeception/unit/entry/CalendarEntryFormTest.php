<?php

namespace humhub\modules\calendar\tests\codeception\unit\entry;

use calendar\CalendarUnitTest;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\content\models\Content;
use DateTime;
use DateInterval;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ActiveQueryContent;

class CalendarEntryFormTest extends CalendarUnitTest
{

    public function testDefaultData()
    {
        $this->becomeUser('Admin');

        $calendarForm = CalendarEntryForm::createEntry(Space::findOne(['id' => 1]));

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
        $this->assertEquals('Test title', $entry->title);
        $this->assertEquals('TestDescription', $entry->description);
        $this->assertEquals(2, $entry->participation_mode);
        $this->assertEquals(Content::VISIBILITY_PUBLIC, $entry->content->visibility);
        $this->assertEquals(0, $entry->all_day);
        $this->assertEquals('2017-06-27 12:00:00', $entry->start_datetime);
        $this->assertEquals('2017-06-28 13:00:00', $entry->end_datetime);
    }

    public function testInitAllDay()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $form = CalendarEntryForm::createEntry($s1, '2020-01-01 00:00:00', '2020-01-02 00:00:00');
        $form->setDefaultTime();

        $this->assertEquals('2020-01-01', $form->start_date);
        $this->assertEquals('10:00 AM', $form->start_time);
        $this->assertEquals('2020-01-01 00:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-01-01', $form->end_date);
        $this->assertEquals('12:00 PM', $form->end_time);
        $this->assertEquals('2020-01-02 00:00:00', $form->entry->end_datetime);
    }

    public function testInitMultipleAllDay()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $form = CalendarEntryForm::createEntry($s1, '2020-01-01 00:00:00', '2020-01-03 00:00:00');
        $form->setDefaultTime();

        $this->assertEquals('2020-01-01', $form->start_date);
        $this->assertEquals('10:00 AM', $form->start_time);
        $this->assertEquals('2020-01-01 00:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-01-02', $form->end_date);
        $this->assertEquals('12:00 PM', $form->end_time);
        $this->assertEquals('2020-01-03 00:00:00', $form->entry->end_datetime);
    }

    public function testInitNonAllDay()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $form = CalendarEntryForm::createEntry($s1, '2020-01-01 14:00:00', '2020-01-01 15:00:00');
        $form->setDefaultTime();

        $this->assertEquals('2020-01-01', $form->start_date);
        $this->assertEquals('02:00 PM', $form->start_time);
        $this->assertEquals('2020-01-01 14:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-01-01', $form->end_date);
        $this->assertEquals('03:00 PM', $form->end_time);
        $this->assertEquals('2020-01-01 15:00:00', $form->entry->end_datetime);
    }

    public function testInitNonAllDayMultiDaySpan()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $form = CalendarEntryForm::createEntry($s1, '2020-01-01 14:00:00', '2020-01-02 15:00:00');
        $form->setDefaultTime();
        $this->assertEquals('2020-01-01', $form->start_date);
        $this->assertEquals('02:00 PM', $form->start_time);
        $this->assertEquals('2020-01-01 14:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-01-02', $form->end_date);
        $this->assertEquals('03:00 PM', $form->end_time);
        $this->assertEquals('2020-01-02 15:00:00', $form->entry->end_datetime);
    }

    public function testLoadSingleAllDay()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $form = CalendarEntryForm::createEntry($s1, '2020-01-01 00:00:00', '2020-01-02 00:00:00');
        $form->setDefaultTime();
        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test',
                'all_day' => '1'
            ],
            'CalendarEntryForm' => [
                'start_date' => '2/1/20',
                'start_time' => '10:00 AM', // will be ignored
                'end_date' => '2/1/20',
                'end_time' => '10:30 AM' // will be ignored
            ]
        ]));

        $this->assertTrue($form->isAllDay());
        $this->assertEquals('2020-02-01', $form->start_date);
        $this->assertEquals('2020-02-01 00:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-01', $form->end_date);
        $this->assertEquals('2020-02-02 00:00:00', $form->entry->end_datetime);
    }

    public function testLoadMultipleAllDay()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $form = CalendarEntryForm::createEntry($s1);
        $form->setDefaultTime();
        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test',
                'all_day' => '1'
            ],
            'CalendarEntryForm' => [
                'start_date' => '2/1/20',
                'start_time' => '10:00 AM', // will be ignored
                'end_date' => '2/2/20',
                'end_time' => '10:30 AM' // will be ignored
            ]
        ]));

        $this->assertTrue($form->isAllDay());
        $this->assertEquals('2020-02-01', $form->start_date);
        $this->assertEquals('2020-02-01 00:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-02', $form->end_date);
        $this->assertEquals('2020-02-03 00:00:00', $form->entry->end_datetime);
    }

    public function testLoadNonAllDay()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $form = CalendarEntryForm::createEntry($s1);
        $form->setDefaultTime();
        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test',
                'all_day' => '0'
            ],
            'CalendarEntryForm' => [
                'start_date' => '2/1/20',
                'start_time' => '10:00 AM',
                'end_date' => '2/1/20',
                'end_time' => '10:30 AM'
            ]
        ]));

        $this->assertFalse($form->isAllDay());
        $this->assertEquals('2020-02-01', $form->start_date);
        $this->assertEquals('2020-02-01 10:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-01', $form->end_date);
        $this->assertEquals('2020-02-01 10:30:00', $form->entry->end_datetime);
    }

    public function testLoadNonAllDayMultiDaySpan()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $form = CalendarEntryForm::createEntry($s1);
        $form->setDefaultTime();
        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test',
                'all_day' => '0'
            ],
            'CalendarEntryForm' => [
                'start_date' => '2/1/20',
                'start_time' => '10:00 AM',
                'end_date' => '2/2/20',
                'end_time' => '10:30 AM'
            ]
        ]));

        $this->assertFalse($form->isAllDay());
        $this->assertEquals('2020-02-01', $form->start_date);
        $this->assertEquals('2020-02-01 10:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-02', $form->end_date);
        $this->assertEquals('2020-02-02 10:30:00', $form->entry->end_datetime);
    }

    public function testLoadAllDayFromEntry()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);
        $entry = $this->createEntry(new DateTime('2020-02-01 00:00:00'), 1, 'Test', $s1);
        $form = new CalendarEntryForm(['entry' => $entry]);
        $this->assertTrue($form->isAllDay());
        $this->assertEquals('2020-02-01', $form->start_date);
        $this->assertEquals('2020-02-01 00:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-01', $form->end_date);
        $this->assertEquals('2020-02-02 00:00:00', $form->entry->end_datetime);
    }

    public function testLoadMultipleAllDayFromEntry()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);
        $entry = $this->createEntry(new DateTime('2020-02-01 00:00:00'), 3, 'Test', $s1);
        $form = new CalendarEntryForm(['entry' => $entry]);

        $this->assertTrue($form->isAllDay());
        $this->assertEquals('2020-02-01', $form->start_date);
        $this->assertEquals('2020-02-01 00:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-03', $form->end_date);
        $this->assertEquals('2020-02-04 00:00:00', $form->entry->end_datetime);
    }

    public function testLoadNonAllDayFromEntry()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);
        $entry = $this->createEntry(new DateTime('2020-02-01 10:00:00'), new DateTime('2020-02-02 12:00:00'), 'Test', $s1);
        $form = new CalendarEntryForm(['entry' => $entry]);

        $this->assertFalse($form->isAllDay());
        $this->assertEquals('2020-02-01', $form->start_date);
        $this->assertEquals('2020-02-01 10:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-02', $form->end_date);
        $this->assertEquals('2020-02-02 12:00:00', $form->entry->end_datetime);
    }

    public function testEditAllDay()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $entry = $this->createEntry(new DateTime('2020-02-01 00:00:00'), 1, 'Test', $s1);

        $form = new CalendarEntryForm(['entry' => $entry]);

        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test',
                'all_day' => '1'
            ],
            'CalendarEntryForm' => [
                'start_date' => '2/2/20',
                'start_time' => '10:00 AM', // will be ignored
                'end_date' => '2/3/20',
                'end_time' => '10:30 AM' // will be ignored
            ]
        ]));

        $this->assertTrue($form->save());

        $this->assertTrue($form->isAllDay());
        $this->assertEquals('2020-02-02', $form->start_date);
        $this->assertEquals('2020-02-02 00:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-03', $form->end_date);
        $this->assertEquals('2020-02-04 00:00:00', $form->entry->end_datetime);
    }

    public function testInvalidStartDate()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $entry = $this->createEntry(new DateTime('2020-02-01 00:00:00'), 1, 'Test', $s1);

        $form = new CalendarEntryForm(['entry' => $entry]);

        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test',
                'all_day' => '0'
            ],
            'CalendarEntryForm' => [
                'start_date' => '03.01.20',
                'start_time' => '10:00 AM',
                'end_date' => '2/3/20',
                'end_time' => '10:30 AM'
            ]
        ]));

        $this->assertFalse($form->save());
        $this->assertNotEmpty($form->getErrors('start_date'));

        $this->assertFalse($form->isAllDay());
        $this->assertEquals('2020-02-01', $form->start_date);
        $this->assertEquals('10:00 AM', $form->start_time);
        $this->assertEquals('2020-02-03', $form->end_date);
        $this->assertEquals('10:30 AM', $form->end_time);
    }

    public function testInvalidEndDate()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $entry = $this->createEntry(new DateTime('2020-02-01 00:00:00'), 1, 'Test', $s1);

        $form = new CalendarEntryForm(['entry' => $entry]);

        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test',
                'all_day' => '0'
            ],
            'CalendarEntryForm' => [
                'start_date' => '2/3/20',
                'start_time' => '10:00 AM',
                'end_date' => '03.01.20',
                'end_time' => '10:30 AM'
            ]
        ]));

        $this->assertFalse($form->save());
        $this->assertNotEmpty($form->getErrors('end_date'));

        $this->assertFalse($form->isAllDay());
        $this->assertEquals('2020-02-03', $form->start_date);
        $this->assertEquals('10:00 AM', $form->start_time);
        $this->assertEquals('2020-02-03', $form->end_date);
        $this->assertEquals('10:30 AM', $form->end_time);
    }

    public function testInvalidDates()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $entry = $this->createEntry(new DateTime('2020-02-01 11:00:00'), new DateTime('2020-02-03 11:30:00'), 'Test', $s1);

        $form = new CalendarEntryForm(['entry' => $entry]);

        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test',
                'all_day' => '0'
            ],
            'CalendarEntryForm' => [
                'start_date' => '03.01.20',
                'start_time' => '10:00 AM',
                'end_date' => '03.01.20',
                'end_time' => '10:30 AM'
            ]
        ]));

        $this->assertFalse($form->save());
        $this->assertNotEmpty($form->getErrors('end_date'));

        $this->assertFalse($form->isAllDay());
        $this->assertEquals((new DateTime('2020-02-01'))->format('Y-m-d'), $form->start_date);
        $this->assertEquals('10:00 AM', $form->start_time);
        $this->assertEquals((new DateTime('2020-02-03'))->format('Y-m-d'), $form->end_date);
        $this->assertEquals('10:30 AM', $form->end_time);
    }

    public function testEditAllDayFail()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $entry = $this->createEntry(new DateTime('2020-02-01 00:00:00'), 1, 'Test', $s1);

        $form = new CalendarEntryForm(['entry' => $entry]);

        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => null,
                'all_day' => '1'
            ],
            'CalendarEntryForm' => [
                'start_date' => '2/2/20',
                'start_time' => '10:00 AM', // will be ignored
                'end_date' => '2/3/20',
                'end_time' => '10:30 AM' // will be ignored
            ]
        ]));

        $this->assertFalse($form->save());

        $this->assertTrue($form->isAllDay());
        $this->assertEquals('2020-02-02', $form->start_date);
        $this->assertEquals('2020-02-02 00:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-03', $form->end_date);
        $this->assertEquals('2020-02-04 00:00:00', $form->entry->end_datetime);
    }

    public function testEditNonAllDay()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $entry = $this->createEntry(new DateTime('2020-02-01 12:00:00'), new DateTime('2020-02-01 13:00:00'), 'Test', $s1);

        $form = new CalendarEntryForm(['entry' => $entry]);

        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test',
                'all_day' => '0'
            ],
            'CalendarEntryForm' => [
                'start_date' => '2/2/20',
                'start_time' => '10:00 AM',
                'end_date' => '2/3/20',
                'end_time' => '10:30 AM'
            ]
        ]));

        $this->assertTrue($form->save());

        $this->assertFalse($form->isAllDay());
        $this->assertEquals('2020-02-02', $form->start_date);
        $this->assertEquals('10:00 AM', $form->start_time);
        $this->assertEquals('2020-02-02 10:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-03', $form->end_date);
        $this->assertEquals('10:30 AM', $form->end_time);
        $this->assertEquals('2020-02-03 10:30:00', $form->entry->end_datetime);
    }

    public function testEditNonAllDayFail()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $entry = $this->createEntry(new DateTime('2020-02-01 12:00:00'), new DateTime('2020-02-01 13:00:00'), 'Test', $s1);

        $form = new CalendarEntryForm(['entry' => $entry]);

        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => null,
                'all_day' => '0'
            ],
            'CalendarEntryForm' => [
                'start_date' => '2/2/20',
                'start_time' => '10:00 AM',
                'end_date' => '2/3/20',
                'end_time' => '10:30 AM'
            ]
        ]));

        $this->assertFalse($form->save());

        $this->assertFalse($form->isAllDay());
        $this->assertEquals('2020-02-02', $form->start_date);
        $this->assertEquals('10:00 AM', $form->start_time);
        $this->assertEquals('2020-02-02 10:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-03', $form->end_date);
        $this->assertEquals('10:30 AM', $form->end_time);
        $this->assertEquals('2020-02-03 10:30:00', $form->entry->end_datetime);
    }

    public function testChangeFromAllDayToNonAllDay()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);
        $entry = $this->createEntry(new DateTime('2020-02-01 00:00:00'), 2, 'Test', $s1);

        $this->assertTrue($entry->isAllDay());

        $form = new CalendarEntryForm(['entry' => $entry]);

        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test',
                'all_day' => '0'
            ],
            'CalendarEntryForm' => [
                'start_date' => '2/2/20',
                'start_time' => '10:00 AM',
                'end_date' => '2/3/20',
                'end_time' => '10:30 AM'
            ]
        ]));

        $this->assertTrue($form->save());

        $this->assertFalse($form->isAllDay());
        $this->assertEquals('2020-02-02', $form->start_date);
        $this->assertEquals('10:00 AM', $form->start_time);
        $this->assertEquals('2020-02-02 10:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-03', $form->end_date);
        $this->assertEquals('10:30 AM', $form->end_time);
        $this->assertEquals('2020-02-03 10:30:00', $form->entry->end_datetime);

    }

    public function testChangeFromNonAllDayToAllDay()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);
        $entry = $this->createEntry(new DateTime('2020-02-01 10:00:00'), new DateTime('2020-02-01 10:30:00'), 'Test', $s1);

        $this->assertFalse($entry->isAllDay());

        $form = new CalendarEntryForm(['entry' => $entry]);

        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test',
                'all_day' => '1'
            ],
            'CalendarEntryForm' => [
                'start_date' => '2/2/20',
                'end_date' => '2/3/20',
            ]
        ]));

        $this->assertTrue($form->save());

        $this->assertTrue($form->isAllDay());
        $this->assertEquals('2020-02-02', $form->start_date);
        $this->assertEquals('2020-02-02 00:00:00', $form->entry->start_datetime);

        $this->assertEquals('2020-02-03', $form->end_date);
        $this->assertEquals('2020-02-04 00:00:00', $form->entry->end_datetime);
    }
}
