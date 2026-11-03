<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\RecurrenceUnitTest;
use humhub\modules\calendar\models\CalendarEntry;

/**
 * Tests loading the existing instances of a recurrent event by their recurrence ids with a single query.
 */
class RecurrenceInstancesQueryTest extends RecurrenceUnitTest
{
    public function testEmptyRecurrenceIdsReturnEmptyResult()
    {
        $this->initRecurrentEvents();

        $this->assertSame([], $this->rootEvent->getRecurrenceQuery()->getRecurrenceInstances([]));
    }

    public function testInstancesAreIndexedByRecurrenceId()
    {
        $this->initRecurrentEvents();

        $first = $this->recurrences[1];
        $second = $this->recurrences[3];

        $result = $this->rootEvent->getRecurrenceQuery()->getRecurrenceInstances([
            $first->getRecurrenceId(),
            $second->getRecurrenceId(),
        ]);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey($first->getRecurrenceId(), $result);
        $this->assertArrayHasKey($second->getRecurrenceId(), $result);
        $this->assertEquals($first->id, $result[$first->getRecurrenceId()]->id);
        $this->assertEquals($second->id, $result[$second->getRecurrenceId()]->id);
    }

    public function testAllInstancesOfThePeriodAreLoaded()
    {
        $this->initRecurrentEvents();

        $recurrenceIds = array_map(static fn(CalendarEntry $instance) => $instance->getRecurrenceId(), $this->recurrences);
        $result = $this->rootEvent->getRecurrenceQuery()->getRecurrenceInstances($recurrenceIds);

        $this->assertCount(7, $result);
        $this->assertEqualsCanonicalizing($recurrenceIds, array_keys($result));
    }

    public function testUnknownRecurrenceIdsAreIgnored()
    {
        $this->initRecurrentEvents();

        $known = $this->recurrences[2];
        $result = $this->rootEvent->getRecurrenceQuery()->getRecurrenceInstances([
            $known->getRecurrenceId(),
            '20300101',
        ]);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey($known->getRecurrenceId(), $result);
        $this->assertArrayNotHasKey('20300101', $result);
    }

    public function testNotYetSavedInstancesAreNotReturned()
    {
        $this->initRecurrentEvents(self::DEFAULT_RRULE, null, false);

        // The instances are only expanded, but not saved
        $unsaved = $this->expand(false);
        $this->assertNotEmpty($unsaved);

        $result = $this->rootEvent->getRecurrenceQuery()->getRecurrenceInstances([$unsaved[0]->getRecurrenceId()]);

        $this->assertSame([], $result);
    }

    public function testOnlyInstancesOfOwnRecurrentEventAreReturned()
    {
        $this->initRecurrentEvents();

        // A second recurrent event with the same dates, so it has the same recurrence ids
        $otherRoot = $this->createEntry($this->getEntryDate(), 1, 'Other Entry', $this->space);
        $this->setDefaults($otherRoot, self::DEFAULT_RRULE);
        $this->assertTrue($otherRoot->save());
        $otherRecurrences = $this->expand(true, $otherRoot);
        $this->assertCount(7, $otherRecurrences);

        $recurrenceId = $this->recurrences[4]->getRecurrenceId();
        $this->assertEquals($recurrenceId, $otherRecurrences[4]->getRecurrenceId());

        $result = $this->rootEvent->getRecurrenceQuery()->getRecurrenceInstances([$recurrenceId]);

        $this->assertCount(1, $result);
        $this->assertEquals($this->recurrences[4]->id, $result[$recurrenceId]->id);
        $this->assertNotEquals($otherRecurrences[4]->id, $result[$recurrenceId]->id);
    }

    public function testContentIsEagerLoaded()
    {
        $this->initRecurrentEvents();

        $result = $this->rootEvent->getRecurrenceQuery()->getRecurrenceInstances([
            $this->recurrences[0]->getRecurrenceId(),
        ]);

        $this->assertCount(1, $result);
        $this->assertTrue(reset($result)->isRelationPopulated('content'));
    }
}
