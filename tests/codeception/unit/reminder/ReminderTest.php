<?php

namespace humhub\modules\calendar\tests\codeception\unit\reminder;

use calendar\CalendarUnitTest;
use DateInterval;
use DateTime;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\calendar\models\reminder\CalendarReminderSent;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

class ReminderTest extends CalendarUnitTest
{
    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function testDeleteReminder()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 1]);
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), new DateInterval('PT1H'), 'Test', $space);
        $reminder = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_DAY, 1, $entry);
        $reminder->save();
        $reminder->acknowledge($entry);

        $this->assertTrue(CalendarReminderSent::check($reminder, $entry));

        $reminder->delete();

        $this->assertEmpty(CalendarReminderSent::findByReminder($reminder, $entry)->all());
    }

    /**
     * @throws \Exception
     */
    public function testSimpleEntryLevelCheck()
    {
        $this->becomeUser('admin');

        // Event starts in one hour and reminder is set to one hour -> should pass
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), new DateInterval('PT1H'), 'Test');
        $reminder = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry);
        $reminder->save();
        $this->assertTrue($reminder->checkMaturity($entry));

        // Event starts in two hour and reminder is set to one hour -> should not pass
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT2H')), new DateInterval('PT1H'), 'Test');
        $reminder->save();

        // Event starts in one day and reminder is set to one hour -> should not pass
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('P1D')), new DateInterval('PT1H'), 'Test');
        $this->assertFalse($reminder->checkMaturity($entry));
    }

    public function testGlobalQueryOrder()
    {
        $reminder1 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 2);
        $reminder2 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 2);
        $reminder3 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1);
        $reminder4 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_WEEK, 1);
        $reminder5 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 1);
        $reminder6 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_WEEK, 2);

        $this->assertTrue($reminder1->save());
        $this->assertTrue($reminder2->save());
        $this->assertTrue($reminder3->save());
        $this->assertTrue($reminder4->save());
        $this->assertTrue($reminder5->save());
        $this->assertTrue($reminder6->save());

        $reminders = CalendarReminder::getDefaults();

        $this->assertEquals($reminder3->id, $reminders[0]->id);
        $this->assertEquals($reminder2->id, $reminders[1]->id);
        $this->assertEquals($reminder5->id, $reminders[2]->id);
        $this->assertEquals($reminder1->id, $reminders[3]->id);
        $this->assertEquals($reminder4->id, $reminders[4]->id);
        $this->assertEquals($reminder6->id, $reminders[5]->id);
    }

    public function testEntryLevelQueryOrder()
    {
        $this->becomeUser('admin');

        $space = Space::findOne(['id' => 1]);
        $user = User::findOne(['id' => 2]);
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), new DateInterval('PT1H'), 'Test', $space);

        $reminder1 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_DAY, 2, $entry, $user);
        $reminder2 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 2, $entry);
        $reminder3 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry);
        $reminder4 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_WEEK, 1, $entry);
        $reminder5 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_DAY, 1, $entry, $user);
        $reminder6 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_WEEK, 2, $entry);
        $reminder7 = CalendarReminder::initDisableEntryLevelDefaults($entry);
        $reminder8 = CalendarReminder::initDisableEntryLevelDefaults($entry, $user);

        $this->assertTrue($reminder1->save());
        $this->assertTrue($reminder2->save());
        $this->assertTrue($reminder3->save());
        $this->assertTrue($reminder4->save());
        $this->assertTrue($reminder5->save());
        $this->assertTrue($reminder6->save());
        $this->assertTrue($reminder7->save());
        $this->assertTrue($reminder8->save());

        $reminders = CalendarReminder::getEntryLevelReminder($entry);

        $this->assertEquals($reminder8->id, $reminders[0]->id);
        $this->assertEquals($reminder5->id, $reminders[1]->id);
        $this->assertEquals($reminder1->id, $reminders[2]->id);
        $this->assertEquals($reminder7->id, $reminders[3]->id);
        $this->assertEquals($reminder3->id, $reminders[4]->id);
        $this->assertEquals($reminder2->id, $reminders[5]->id);
        $this->assertEquals($reminder4->id, $reminders[6]->id);
        $this->assertEquals($reminder6->id, $reminders[7]->id);
    }

    public function testGetEntryLevelReminder()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 1]);
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), new DateInterval('PT1H'), 'Test', $space);

        // Create Global Default Reminder
        $reminder1 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1);
        $this->assertTrue($reminder1->save());

        // Create Space Level Reminder
        $reminder2 = CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 2, $entry->content->container);
        $this->assertTrue($reminder2->save());

        // Overwrite Space Level for this entry
        $reminder3 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 3, $entry);
        $this->assertTrue($reminder3->save());

        // Overwrite by User level reminder for this entry
        $reminder4 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 4, $entry, User::findOne(['id' => 1]));
        $this->assertTrue($reminder4->save());

        // Add another Space Level reminder for this entry
        $reminder5 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 5, $entry);
        $this->assertTrue($reminder5->save());

        // Add another User Level reminder for this entry for another user
        $reminder6 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 6, $entry, User::findOne(['id' => 2]));
        $this->assertTrue($reminder6->save());

        $reminders = CalendarReminder::getEntryLevelReminder($entry);
        $this->assertCount(4, $reminders);

        // Make sure the User level reminder sorted first
        $this->assertEquals($reminder6->id, $reminders[0]->id);
        $this->assertEquals($reminder4->id, $reminders[1]->id);
        $this->assertEquals($reminder3->id, $reminders[2]->id);
        $this->assertEquals($reminder5->id, $reminders[3]->id);
    }

    public function testDaysInFuture()
    {
        $reminder1 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_WEEK, 3);
        $this->assertTrue($reminder1->save());

        $reminder2 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 31);
        $this->assertTrue($reminder2->save());

        $this->assertEquals(31, CalendarReminder::getMaxReminderDaysInFuture());
    }

    public function testDaysInFuture2()
    {
        $reminder1 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_WEEK, 4);
        $this->assertTrue($reminder1->save());

        $reminder2 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 23);
        $this->assertTrue($reminder2->save());

        $this->assertEquals(28, CalendarReminder::getMaxReminderDaysInFuture());
    }
}