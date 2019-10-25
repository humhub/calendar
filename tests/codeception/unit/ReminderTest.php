<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\CalendarUnitTest;
use DateInterval;
use DateTime;
use humhub\modules\calendar\models\CalendarReminder;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

class ReminderTest  extends CalendarUnitTest
{
    /**
     * @throws \Exception
     */
    public function testSimpleEntryLevelCheck()
    {
        $this->becomeUser('admin');

        // Event starts in one hour and reminder is set to one hour -> should pass
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), new DateInterval('PT1H'), 'Test');
        $reminder = $this->createReminder(CalendarReminder::UNIT_HOUR, 1, $entry);
        $this->assertTrue($reminder->checkModelState($entry));

        // Event starts in two hour and reminder is set to one hour -> should not pass
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT2H')), new DateInterval('PT1H'), 'Test');
        $reminder = $this->createReminder(CalendarReminder::UNIT_HOUR, 1, $entry);
        $this->assertFalse($reminder->checkModelState($entry));

        // Event starts in one day and reminder is set to one hour -> should not pass
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('P1D')), new DateInterval('PT1H'), 'Test');
        $reminder = $this->createReminder(CalendarReminder::UNIT_HOUR, 1, $entry);
        $this->assertFalse($reminder->checkModelState($entry));
    }

    public function testGetByModel()
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

        $reminders = CalendarReminder::getByEntry($entry);
        $this->assertCount(4, $reminders);

        // Make sure the User level reminder sorted first
        $this->assertEquals($reminder6->id, $reminders[0]->id);
        $this->assertEquals($reminder4->id, $reminders[1]->id);
        $this->assertEquals($reminder3->id, $reminders[2]->id);
        $this->assertEquals($reminder5->id, $reminders[3]->id);
    }
}