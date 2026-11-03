<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\RecurrenceUnitTest;
use DateInterval;
use DateTime;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Tests `CalendarService::getUpcomingEntries()` with a limit on recurrent events, which only expands the needed
 * number of instances.
 */
class UpcomingEntriesLimitTest extends RecurrenceUnitTest
{
    private CalendarService $calendarService;

    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getModule('calendar')->set(CalendarService::class, ['class' => CalendarService::class]);
        $this->calendarService = Yii::$app->getModule('calendar')->get(CalendarService::class);
    }

    public function testUpcomingEntriesOfRecurrentEventRespectLimit()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, new DateTime(), false);

        $entries = $this->calendarService->getUpcomingEntries(null, 31, 3);

        $this->assertCount(3, $entries);

        // The instances are consecutive days in ascending order
        $this->assertEquals(
            $entries[0]->getStartDateTime()->modify('+1 day'),
            $entries[1]->getStartDateTime(),
        );
        $this->assertEquals(
            $entries[1]->getStartDateTime()->modify('+1 day'),
            $entries[2]->getStartDateTime(),
        );
    }

    public function testUpcomingEntriesWithLimitDoNotPersistInstances()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, new DateTime(), false);

        $this->calendarService->getUpcomingEntries(null, 31, 5);

        $this->assertEquals(0, (int) CalendarEntry::find()->where(['parent_event_id' => $this->rootEvent->id])->count());
    }

    public function testLimitIsAppliedAcrossMultipleRecurrentEvents()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, new DateTime(), false);

        $secondRoot = $this->createEntry(new DateTime(), 1, 'Second Recurrent Entry', $this->space);
        $this->setDefaults($secondRoot, self::DEFAULT_RRULE);
        $this->assertTrue($secondRoot->save());

        $entries = $this->calendarService->getUpcomingEntries(null, 31, 4);

        $this->assertCount(4, $entries);

        // Sorted by start date
        $starts = array_map(static fn($entry) => $entry->getStartDateTime(), $entries);
        $sorted = $starts;
        usort($sorted, static fn(DateTime $a, DateTime $b) => $a <=> $b);
        $this->assertEquals($sorted, $starts);
    }

    /**
     * The moved instance is saved with its original recurrence id (5th instance), which is behind the limit.
     */
    public function testMovedInstanceBehindLimitIsListed()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, new DateTime(), false);

        $today = (new DateTime())->setTime(0, 0, 0);
        $instances = $this->rootEvent->getRecurrenceQuery()->expandEvent(
            $today,
            (clone $today)->add(new DateInterval('P7D')),
            true,
        );
        $this->assertGreaterThanOrEqual(6, count($instances));

        // Move the 5th instance to tomorrow 10:00
        $moved = $instances[4];
        $tomorrow = (clone $today)->add(new DateInterval('P1D'))->format('n/j/y');
        $form = new CalendarEntryForm(['entry' => $moved]);
        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Moved title',
                'all_day' => '0',
                'description' => 'Moved description',
                'participation_mode' => CalendarEntryParticipation::PARTICIPATION_MODE_NONE,
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'start_date' => $tomorrow,
                'start_time' => '10:00 AM',
                'end_date' => $tomorrow,
                'end_time' => '10:30 AM',
                'reminder' => 1,
                'recurring' => 1,
            ],
            'RecurrenceFormModel' => [
                'recurrenceEditMode' => RecurrenceFormModel::EDIT_MODE_THIS,
            ],
        ]));
        $this->assertTrue($form->save());

        $entries = $this->calendarService->getUpcomingEntries(null, 31, 3);
        $ids = ArrayHelper::getColumn($entries, 'id');

        $this->assertCount(3, $entries);
        $this->assertContains($moved->id, $ids);
        $this->assertCount(3, array_unique($ids));
    }
}
