<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\RecurrenceUnitTest;
use DateTime;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\calendar\models\recurrence\CalendarRecurrenceExpand;
use yii\helpers\ArrayHelper;

/**
 * Tests the `$limit` parameter of the recurrence expansion, which is used to expand only the needed number of
 * instances of a recurrent event.
 *
 * The root event starts on 2019-12-01 and repeats daily, the expanded period is 2019-12-01 till 2019-12-07 (7 instances).
 */
class RecurrenceExpandLimitTest extends RecurrenceUnitTest
{
    public function testLimitReturnsOnlyFirstInstances()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, null, false);

        $result = $this->expandLimited(3);

        $this->assertCount(3, $result);
        $this->assertEquals('2019-12-01 00:00:00', $result[0]->start_datetime);
        $this->assertEquals('2019-12-02 00:00:00', $result[1]->start_datetime);
        $this->assertEquals('2019-12-03 00:00:00', $result[2]->start_datetime);
    }

    public function testLimitGreaterThanNumberOfInstancesReturnsAllInstances()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, null, false);

        $this->assertCount(7, $this->expandLimited(100));
    }

    public function testLimitEqualToNumberOfInstancesReturnsAllInstances()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, null, false);

        $this->assertCount(7, $this->expandLimited(7));
    }

    public function testNoLimitReturnsAllInstances()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, null, false);

        $this->assertCount(7, $this->expandLimited(null));
        $this->assertCount(7, $this->expandLimited(0));
    }

    public function testLimitThroughRecurrenceQuery()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, null, false);

        $result = [];
        $this->rootEvent->getRecurrenceQuery()->expandEvent(
            $this->getPeriodStart(),
            $this->getPeriodEnd(),
            false,
            $result,
            2,
        );

        $this->assertCount(2, $result);
        $this->assertEquals('2019-12-01 00:00:00', $result[0]->start_datetime);
        $this->assertEquals('2019-12-02 00:00:00', $result[1]->start_datetime);
    }

    public function testLimitedExpandWithoutSaveDoesNotPersistInstances()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, null, false);

        $this->assertCount(3, $this->expandLimited(3, false));
        $this->assertEquals(0, $this->countPersistedInstances());
    }

    public function testLimitedExpandWithSaveOnlyPersistsLimitedInstances()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, null, false);

        $result = $this->expandLimited(3, true);

        $this->assertCount(3, $result);
        $this->assertEquals(3, $this->countPersistedInstances());
        foreach ($result as $instance) {
            $this->assertNotNull($instance->id);
        }
    }

    public function testLimitedExpandUsesExistingInstances()
    {
        // The 7 instances of the period are already saved
        $this->initRecurrentEvents();
        $this->assertCount(7, $this->recurrences);
        $this->assertEquals(7, $this->countPersistedInstances());

        $result = $this->expandLimited(3);

        $this->assertCount(3, $result);
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($this->recurrences[$i]->id, $result[$i]->id);
        }

        // No additional instances are created
        $this->assertEquals(7, $this->countPersistedInstances());
    }

    /**
     * An instance moved to another date is only found by its original recurrence id, which is behind the limit here.
     * It has to be listed anyway, as it takes place within the expanded period.
     */
    public function testMovedInstanceBehindLimitIsStillListed()
    {
        $this->initRecurrentEvents();

        // Move the instance of 2019-12-06 to 2019-12-03 10:00
        $movedId = $this->recurrences[5]->id;
        $this->moveInstanceToThirdDay($this->recurrences[5]);

        $result = $this->expandLimited(2);

        $this->assertCount(3, $result);
        $this->assertEqualsCanonicalizing(
            [$this->recurrences[0]->id, $this->recurrences[1]->id, $movedId],
            ArrayHelper::getColumn($result, 'id'),
        );

        $moved = CalendarEntry::findOne(['id' => $movedId]);
        $this->assertEquals('2019-12-03 10:00:00', $moved->start_datetime);
    }

    /**
     * An instance within the limit, which has been moved, must not be listed twice.
     */
    public function testMovedInstanceWithinLimitIsNotDuplicated()
    {
        $this->initRecurrentEvents();

        // Move the instance of 2019-12-02 to 2019-12-03 10:00
        $movedId = $this->recurrences[1]->id;
        $this->moveInstanceToThirdDay($this->recurrences[1]);

        $result = $this->expandLimited(3);
        $ids = ArrayHelper::getColumn($result, 'id');

        $this->assertCount(3, $result);
        $this->assertCount(3, array_unique($ids));
        $this->assertEqualsCanonicalizing(
            [$this->recurrences[0]->id, $movedId, $this->recurrences[2]->id],
            $ids,
        );
    }

    /**
     * An instance moved out of the expanded period must not be listed.
     */
    public function testInstanceMovedOutOfPeriodIsNotListedWithLimit()
    {
        $this->initRecurrentEvents();

        // Move the instance of 2019-12-06 to 2019-12-20
        $form = new CalendarEntryForm(['entry' => $this->recurrences[5]]);
        $this->assertTrue($form->load($this->getMoveFormData('12/20/19')));
        $this->assertTrue($form->save());

        $result = $this->expandLimited(2);

        $this->assertCount(2, $result);
        $this->assertNotContains($this->recurrences[5]->id, ArrayHelper::getColumn($result, 'id'));
    }

    /**
     * @return CalendarEntry[]
     */
    private function expandLimited(?int $limit, bool $save = false): array
    {
        $result = [];
        return CalendarRecurrenceExpand::expand(
            $this->rootEvent,
            $this->getPeriodStart(),
            $this->getPeriodEnd(),
            $result,
            $save,
            $limit,
        );
    }

    private function getPeriodStart(): DateTime
    {
        return (new DateTime())->setDate(2019, 12, 1)->setTime(0, 0, 0);
    }

    private function getPeriodEnd(): DateTime
    {
        return (new DateTime())->setDate(2019, 12, 7)->setTime(23, 59, 59);
    }

    private function countPersistedInstances(): int
    {
        return (int) CalendarEntry::find()->where(['parent_event_id' => $this->rootEvent->id])->count();
    }

    private function moveInstanceToThirdDay(CalendarEntry $instance): void
    {
        $form = new CalendarEntryForm(['entry' => $instance]);
        $this->assertTrue($form->load($this->getMoveFormData('12/3/19')));
        $this->assertTrue($form->save());
        $this->assertEquals('2019-12-03 10:00:00', $form->entry->start_datetime);
    }

    private function getMoveFormData(string $date): array
    {
        return [
            'CalendarEntry' => [
                'title' => 'Moved title',
                'all_day' => '0',
                'description' => 'Moved description',
                'participation_mode' => CalendarEntryParticipation::PARTICIPATION_MODE_NONE,
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'start_date' => $date,
                'start_time' => '10:00 AM',
                'end_date' => $date,
                'end_time' => '10:30 AM',
                'reminder' => 1,
                'recurring' => 1,
            ],
            'RecurrenceFormModel' => [
                'recurrenceEditMode' => RecurrenceFormModel::EDIT_MODE_THIS,
            ],
        ];
    }
}
