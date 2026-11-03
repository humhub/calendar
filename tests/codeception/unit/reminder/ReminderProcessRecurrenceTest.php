<?php

namespace humhub\modules\calendar\tests\codeception\unit\reminder;

use calendar\CalendarUnitTest;
use DateInterval;
use DateTime;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\calendar\models\reminder\CalendarReminderSent;
use humhub\modules\calendar\models\reminder\ReminderService;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use Yii;

/**
 * Tests the reminder process with many (recurrent) events.
 */
class ReminderProcessRecurrenceTest extends CalendarUnitTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->installationState->setInstalled();

        Yii::$app->getModule('calendar')->set(CalendarService::class, ['class' => CalendarService::class]);
        // Make sure we don't receive content created notifications
        Membership::updateAll(['send_notifications' => 0]);
    }

    /**
     * The reminder process handles all upcoming events, not only the first `reminderProcessEventLimit` ones.
     */
    public function testRemindersAreNotLimitedByEventLimit()
    {
        $module = Yii::$app->getModule('calendar');
        $originalLimit = $module->reminderProcessEventLimit;
        $module->reminderProcessEventLimit = 2;

        try {
            $space = Space::findOne(['id' => 3]);
            $this->becomeUser('admin');

            $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1);
            $this->assertTrue($reminder->save());

            // Entries begin exactly in one hour
            $start = (new DateTime())->add(new DateInterval('PT1H'));
            $entries = [];
            for ($i = 0; $i < 4; $i++) {
                $entries[] = $this->createEntry(clone $start, null, 'Single ' . $i, $space);
            }

            $recurrentRoots = [];
            for ($i = 0; $i < 2; $i++) {
                $root = $this->createEntry(clone $start, null, 'Recurrent ' . $i, $space);
                $root->rrule = 'FREQ=DAILY;INTERVAL=1';
                $this->assertTrue($root->saveEvent());
                $recurrentRoots[] = $root;
            }

            (new ReminderService())->sendAllReminder();

            foreach ($entries as $entry) {
                $this->assertTrue(CalendarReminderSent::check($reminder, $entry), $entry->title . ' was not reminded');
            }

            foreach ($recurrentRoots as $root) {
                $firstInstance = $this->findFirstInstance($root);
                $this->assertNotNull($firstInstance, $root->title . ' instance was not persisted');
                $this->assertTrue(CalendarReminderSent::check($reminder, $firstInstance), $root->title . ' was not reminded');
            }
        } finally {
            $module->reminderProcessEventLimit = $originalLimit;
        }
    }

    /**
     * Instances of recurrent events are only persisted by the reminder process once a reminder is due.
     */
    public function testUpcomingRecurrentInstancesAreNotPersistedUntilReminderIsDue()
    {
        $space = Space::findOne(['id' => 3]);
        $this->becomeUser('admin');

        // Global reminder one hour before start
        $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1);
        $this->assertTrue($reminder->save());

        // An entry level reminder of 3 days widens the period of the reminder process to 3 days
        $wideEntry = $this->createEntry((new DateTime())->add(new DateInterval('P2D')), null, 'Wide', $space);
        $wideReminder = CalendarReminder::initEntryLevel(CalendarReminder::UNIT_DAY, 3, $wideEntry);
        $this->assertTrue($wideReminder->save());

        // Daily event starting in two hours: 3 instances are within the period, but none is due yet
        $root = $this->createEntry((new DateTime())->add(new DateInterval('PT2H')), null, 'Recurrent', $space);
        $root->rrule = 'FREQ=DAILY;INTERVAL=1';
        $this->assertTrue($root->saveEvent());

        (new ReminderService())->sendAllReminder();

        $this->assertEquals(0, $this->countInstances($root));

        // The entry level reminder of the other entry is handled as usual
        $this->assertTrue(CalendarReminderSent::check($wideReminder, $wideEntry));
    }

    /**
     * Only the instance with a due reminder of a recurrent event is persisted.
     */
    public function testOnlyInstanceWithDueReminderIsPersisted()
    {
        $space = Space::findOne(['id' => 3]);
        $this->becomeUser('admin');

        $reminder = CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1);
        $this->assertTrue($reminder->save());

        // An entry level reminder of 3 days widens the period of the reminder process to 3 days
        $wideEntry = $this->createEntry((new DateTime())->add(new DateInterval('P2D')), null, 'Wide', $space);
        $this->assertTrue(CalendarReminder::initEntryLevel(CalendarReminder::UNIT_DAY, 3, $wideEntry)->save());

        // Daily event starting in one hour: the reminder of the first instance is due, the next ones are not
        $root = $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, 'Recurrent', $space);
        $root->rrule = 'FREQ=DAILY;INTERVAL=1';
        $this->assertTrue($root->saveEvent());

        (new ReminderService())->sendAllReminder();

        $this->assertEquals(1, $this->countInstances($root));
        $firstInstance = $this->findFirstInstance($root);
        $this->assertNotNull($firstInstance);
        $this->assertTrue(CalendarReminderSent::check($reminder, $firstInstance));

        // The reminder is not sent again
        $sentCount = CalendarReminderSent::find()->count();
        (new ReminderService())->sendAllReminder();
        $this->assertEquals($sentCount, CalendarReminderSent::find()->count());
        $this->assertEquals(1, $this->countInstances($root));
    }

    /**
     * Returns the persisted instance, expandUpcoming() is not used here as it saves instances by default.
     */
    private function findFirstInstance(CalendarEntry $root): ?CalendarEntry
    {
        return CalendarEntry::find()
            ->where(['parent_event_id' => $root->id])
            ->orderBy(['start_datetime' => SORT_ASC])
            ->one();
    }

    private function countInstances(CalendarEntry $root): int
    {
        return (int) CalendarEntry::find()->where(['parent_event_id' => $root->id])->count();
    }
}
