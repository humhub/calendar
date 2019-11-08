<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use humhub\modules\calendar\interfaces\ReminderService;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\notifications\Remind;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;
use calendar\CalendarUnitTest;
use DateInterval;
use DateTime;
use humhub\modules\calendar\Events;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\calendar\models\reminder\CalendarReminderSent;

class ReminderProcessTest  extends CalendarUnitTest
{
    protected function setUp()
    {
        parent::setUp();
        Events::onBeforeRequest();
        // Make sure we don't receive content created notifications
        Membership::updateAll(['send_notifications' => 0]);
    }

    /**
     * This test makes sure that if there are two matching user entry reminder only one will sent out a reminder, but both will
     * be invalidated.
     *
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function testMultipleMatchingUserEntryReminder()
    {
        $space = Space::findOne(['id' => 3]);
        $this->becomeUser('admin');
        $user = User::findOne(['id' => 1]);

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        $reminder = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry, $user);
        $this->assertTrue($reminder->save());

        $reminder2 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 2, $entry, $user);
        $this->assertTrue($reminder2->save());


        (new ReminderService())->sendAllReminder();

        // Make sure both reminder got invalidated
        $this->assertTrue(CalendarReminderSent::check($reminder, $entry));
        $this->assertTrue(CalendarReminderSent::check($reminder2, $entry));

        (new ReminderService())->sendAllReminder();

        $this->assertMailSent(1);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 1);
    }

    /**
     * This test makes sure that if there are two matching container level entry reminder only one will sent out a reminder, but both will
     * be invalidated.
     *
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function testMultipleMatchingEntryReminder()
    {
        $space = Space::findOne(['id' => 3]);
        $this->becomeUser('admin');

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        $reminder = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry);
        $this->assertTrue($reminder->save());

        $reminder2 = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 2, $entry);
        $this->assertTrue($reminder2->save());


        (new ReminderService())->sendAllReminder();

        // Make sure both reminder got invalidated
        $this->assertTrue(CalendarReminderSent::check($reminder, $entry));
        $this->assertTrue(CalendarReminderSent::check($reminder2, $entry));

        (new ReminderService())->sendAllReminder();

        $this->assertMailSent(3);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 1);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 2);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 3);
    }

    /**
     * This test makes sure that if there are two matching global default reminder only one will sent out a reminder, but both will
     * be invalidated.
     *
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function testMultipleMatchingDefaultReminder()
    {
        $space = Space::findOne(['id' => 3]);

        $this->becomeUser('admin');
        $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1);
        $this->assertTrue($reminder->save());

        $reminder2 = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 2);
        $this->assertTrue($reminder2->save());

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);


        (new ReminderService())->sendAllReminder();

        // Make sure both reminder got invalidated
        $this->assertTrue(CalendarReminderSent::check($reminder, $entry));
        $this->assertTrue(CalendarReminderSent::check($reminder2, $entry));

        (new ReminderService())->sendAllReminder();

        $this->assertMailSent(3);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 1);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 2);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 3);
    }

    /**
     * Tests overwriting a global default reminder by a container reminder
     *
     * In this test we create
     *  - a gobal reminder -1 days
     *  - a container level reminder -1 hours
     *  - an event starting within in exactly one day
     *  - an event starting within in exactly one hour
     *
     * The global reminder should be skipped, and the space level reminder should send a reminder for event starting within an hour
     *
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function testOverwriteContainerDefaultByContainerWideEntryLevel()
    {
        $space = Space::findOne(['id' => 3]);

        $this->becomeUser('admin');
        $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 1 );
        $this->assertTrue($reminder->save());

        $spaceReminder = CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR,1, $space);
        $this->assertTrue($spaceReminder->save());

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        // Entry2 begins exactly in one hour
        $entry2 = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test2',  $space);

        $entryLevelContainerWideReminder = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR,1, $entry2);
        $this->assertTrue($entryLevelContainerWideReminder->save());

        (new ReminderService())->sendAllReminder();

        $this->assertFalse(CalendarReminderSent::check($reminder, $entry));
        $this->assertFalse(CalendarReminderSent::check($reminder, $entry2));
        $this->assertTrue(CalendarReminderSent::check($spaceReminder, $entry));
        $this->assertFalse(CalendarReminderSent::check($spaceReminder, $entry2));
        $this->assertFalse(CalendarReminderSent::check($entryLevelContainerWideReminder, $entry));
        $this->assertTrue(CalendarReminderSent::check($entryLevelContainerWideReminder, $entry2));

        $this->assertMailSent(6);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 1);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 2);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 3);

        $this->assertHasNotification(Remind::class, $entry2, $entry2->content->createdBy->id, 1);
        $this->assertHasNotification(Remind::class, $entry2, $entry2->content->createdBy->id, 2);
        $this->assertHasNotification(Remind::class, $entry2, $entry2->content->createdBy->id, 3);
    }

    /**
     * Tests overwriting a global default reminder by a container reminder
     *
     * In this test we create
     *  - a gobal reminder -1 days
     *  - a container level reminder -1 hours
     *  - an event starting within in exactly one day
     *  - an event starting within in exactly one hour
     *
     * The global reminder should be skipped, and the space level reminder should send a reminder for event starting within an hour
     *
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function testOverwriteContainerLevelByUserLevelEntry()
    {
        $space = Space::findOne(['id' => 3]);
        $this->becomeUser('admin');
        $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 1 );
        $this->assertTrue($reminder->save());

        $spaceReminder = CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR,1, $space);
        $this->assertTrue($spaceReminder->save());

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        // Entry2 begins exactly in one hour
        $entry2 = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test2',  $space);

        $entryLevelContainerWideReminder = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR,1, $entry2);
        $this->assertTrue($entryLevelContainerWideReminder->save());

        $userEntryLevelContainerWideReminder = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR,1, $entry2, User::findOne(['id' => 1]));
        $this->assertTrue($userEntryLevelContainerWideReminder->save());

        (new ReminderService())->sendAllReminder();

        $this->assertFalse(CalendarReminderSent::check($reminder, $entry));
        $this->assertFalse(CalendarReminderSent::check($reminder, $entry2));
        $this->assertTrue(CalendarReminderSent::check($spaceReminder, $entry));
        $this->assertFalse(CalendarReminderSent::check($spaceReminder, $entry2));
        $this->assertFalse(CalendarReminderSent::check($entryLevelContainerWideReminder, $entry));
        $this->assertTrue(CalendarReminderSent::check($entryLevelContainerWideReminder, $entry2));
        $this->assertFalse(CalendarReminderSent::check($userEntryLevelContainerWideReminder, $entry));
        $this->assertTrue(CalendarReminderSent::check($userEntryLevelContainerWideReminder, $entry2));

        $userEntryLevelContainerWideReminder->refresh();
        $entryLevelContainerWideReminder->refresh();
        $reminder->refresh();
        $spaceReminder->refresh();
        $this->assertEquals(0, $userEntryLevelContainerWideReminder->active);
        $this->assertEquals(0, $entryLevelContainerWideReminder->active);
        $this->assertEquals(1, $reminder->active);
        $this->assertEquals(1, $spaceReminder->active);

        $this->assertMailSent(6);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 1);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 2);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 3);

        $this->assertHasNotification(Remind::class, $entry2, $entry2->content->createdBy->id, 1);
        $this->assertHasNotification(Remind::class, $entry2, $entry2->content->createdBy->id, 2);
        $this->assertHasNotification(Remind::class, $entry2, $entry2->content->createdBy->id, 3);
    }

    /**
     * Tests overwriting a global default reminder by a container reminder
     *
     * In this test we create
     *  - a gobal reminder -1 days
     *  - a container level reminder -1 hours
     *  - an event starting within in exactly one day
     *  - an event starting within in exactly one hour
     *
     * The global reminder should be skipped, and the space level reminder should send a reminder for event starting within an hour
     *
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function testOverwriteGlobalDefaultByContainer()
    {
        $space = Space::findOne(['id' => 3]);
        $this->becomeUser('admin');
        $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 1 );
        $this->assertTrue($reminder->save());

        $spaceReminder = CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR,1, $space);
        $this->assertTrue($spaceReminder->save());


        // Entry begins exactly in one day
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('P1D')), null, 'Test',  $space);

        // Entry2 begins exactly in one hour
        $entry2 = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test2',  $space);

        (new ReminderService())->sendAllReminder();

        $this->assertFalse(CalendarReminderSent::check($reminder, $entry));
        $this->assertFalse(CalendarReminderSent::check($reminder, $entry2));
        $this->assertFalse(CalendarReminderSent::check($spaceReminder, $entry));
        $this->assertTrue(CalendarReminderSent::check($spaceReminder, $entry2));

        $this->assertMailSent(3);
        $this->assertHasNotification(Remind::class, $entry2, $entry2->content->createdBy->id, 1);
        $this->assertHasNotification(Remind::class, $entry2, $entry2->content->createdBy->id, 2);
        $this->assertHasNotification(Remind::class, $entry2, $entry2->content->createdBy->id, 3);
    }

    /**
     * Tests makes sure that a non mature reminder is not sent.
     *
     * In this test we create
     *  - a reminder one day before
     *  - an event in two days
     *
     * The reminder process should skipt the reminder
     *
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function testSingleGlobalReminderNotSent()
    {
        $this->becomeUser('admin');

        $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 1);
        $this->assertTrue($reminder->save());
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('P2D')), null, 'Test',  Space::findOne(['id' => 3]));

        // Check Only sent to not declined user
        (new ReminderService())->sendAllReminder();

        $this->assertFalse(CalendarReminderSent::check($reminder, $entry));

        $this->assertSentEmail( 0);
    }

    public function testSentReminderToNonSpaceMemberParticipant()
    {
        $this->becomeUser('admin');

        $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 1 );
        $this->assertTrue($reminder->save());
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('P1D')), null, 'Test',  Space::findOne(['id' => 3]));
        $entry->participation_mode = CalendarEntry::PARTICIPATION_MODE_ALL;
        $entry->save();

        $entry->setParticipant(User::findOne(['id' => 4]));

        // Check Only sent to not declined user
        (new ReminderService())->sendAllReminder();

        $this->assertTrue(CalendarReminderSent::check($reminder, $entry));

        $this->assertMailSent(4);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 1);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 2);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 3);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 4);
    }

    /**
     * Tests a single global default reminder with an event with participation mode all
     *
     * In this test we create
     *  - a reminder one day before
     *  - an event in one days with participation mode all
     *
     * The reminder should be sent to all space members
     *
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function testSingleGlobalReminderOnSpaceParticipationAll()
    {
        $this->becomeUser('admin');

        $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 1 );
        $this->assertTrue($reminder->save());
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('P1D')), null, 'Test',  Space::findOne(['id' => 3]));
        $entry->participation_mode = CalendarEntry::PARTICIPATION_MODE_ALL;
        $entry->save();

        // Check Only sent to not declined user
        (new ReminderService())->sendAllReminder();

        $this->assertTrue(CalendarReminderSent::check($reminder, $entry));

        $this->assertMailSent(3);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 1);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 2);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 3);
    }

    /**
     * Tests a single global default reminder with an event with participation mode all and one declining user
     *
     * In this test we create
     *  - a reminder one day before
     *  - an event in one days with participation mode all
     *  - one user declines the event
     *
     * The reminder should be sent to all space members except the declining user
     *
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function testSingleGlobalReminderOnSpaceParticipationAllDeclined()
    {
        $this->becomeUser('admin');

        $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 1 );
        $this->assertTrue($reminder->save());
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('P1D')), null, 'Test',  Space::findOne(['id' => 3]));
        $entry->participation_mode = CalendarEntry::PARTICIPATION_MODE_ALL;
        $entry->save();

        // User2 declines
        $entry->setParticipant(User::findOne(['id' => 3]), CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED);

        (new ReminderService())->sendAllReminder();

        $this->assertTrue(CalendarReminderSent::check($reminder, $entry));

        // Check Only sent to not declined user
        $this->assertMailSent(2);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 1);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 2);
        $this->assertHasNoNotification(Remind::class, $entry, $entry->content->createdBy->id, 3);
    }

    /**
     * Tests a single global default reminder with an event with participation mode none
     *
     * In this test we create
     *  - a reminder one day before
     *  - an event in one days with participation mode none
     *
     * The reminder should be sent to all space members
     *
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function testSingleGlobalReminderOnSpaceParticipationNone()
    {
        $this->becomeUser('admin');
        $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_DAY, 1 );
        $this->assertTrue($reminder->save());
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('P1D')), null, 'Test',  Space::findOne(['id' => 3]));
        $entry->participation_mode = CalendarEntry::PARTICIPATION_MODE_NONE;
        $entry->save();

        (new ReminderService())->sendAllReminder();

        $this->assertTrue(CalendarReminderSent::check($reminder, $entry));

        // Check Only sent to not declined user
        $this->assertMailSent(3);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 1);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 2);
        $this->assertHasNotification(Remind::class, $entry, $entry->content->createdBy->id, 3);
    }
}