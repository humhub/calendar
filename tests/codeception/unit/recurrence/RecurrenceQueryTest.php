<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\CalendarUnitTest;
use calendar\RecurrenceUnitTest;
use DateInterval;
use DateTime;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\calendar\models\recurrence\CalendarRecurrenceExpand;
use humhub\modules\space\models\Space;
use Recurr\Frequency;

class RecurrenceQueryTest extends RecurrenceUnitTest
{
    public function testExpandMultipleDays()
    {
        $this->becomeUser('Admin');
        $this->space = Space::findOne(['id' => 1]);

        $from = $this->getEntryDate()->setTime(0,0,0);
        $to = $this->getEntryDate()->modify('+1 day')->setTime(23,59,59);

        $this->rootEvent = $this->createEntry($from, 'P1D', 'Two Day Event', $this->space);
        $this->setDefaults($this->rootEvent, 'FREQ=DAILY;INTERVAL=2');
        $this->assertTrue($this->rootEvent->save());
        $recurrences = $this->expand();
        $this->assertEquals('2019-12-01 00:00:00', $recurrences[0]->start_datetime);
        $this->assertEquals('2019-12-02 23:59:59', $recurrences[0]->end_datetime);

        $this->assertEquals('2019-12-03 00:00:00', $recurrences[1]->start_datetime);
        $this->assertEquals('2019-12-04 23:59:59', $recurrences[1]->end_datetime);
    }

    /**
     * @throws \Exception
     */
    public function testExpandUpcomingEventOnRootNonExpanded()
    {
        // Create a recurrent event starting today repeating every two days, but don't expand yet
        $this->initRecurrentEvents('FREQ=DAILY;INTERVAL=2', new DateTime(), false);
        $instances = $this->rootEvent->getRecurrenceQuery()->expandUpcoming(2);
        $this->assertCount(2, $instances);

        $today = (new DateTime())->setTime(0,0,0);
        $this->assertEquals($today, $instances[0]->getStartDateTime());

        $next = $today->modify('+2 day');
        $this->assertEquals($next, $instances[1]->getStartDateTime());
    }

    public function testExpandUpcomingEventOnRootExpanded()
    {
        // Create a recurrent event starting today repeating every two days, but don't expand yet
        $this->initRecurrentEvents('FREQ=DAILY;INTERVAL=2', new DateTime(), false);

        $today = (new DateTime())->setTime(0,0,0);

        $expandEnd = (clone $today)->modify('+3 day');
        $existingRecurrences = $this->rootEvent->getRecurrenceQuery()->expandEvent($today, $expandEnd, true);

        $upcomingRecurrences = $this->rootEvent->getRecurrenceQuery()->expandUpcoming(2);
        $this->assertCount(2, $upcomingRecurrences);


        $this->assertEquals($today, $upcomingRecurrences[0]->getStartDateTime());
        $this->assertEquals($existingRecurrences[0]->id, $upcomingRecurrences[0]->getId());


        $next = $today->modify('+2 day');
        $this->assertEquals($next, $upcomingRecurrences[1]->getStartDateTime());
        $this->assertEquals($existingRecurrences[1]->id, $upcomingRecurrences[1]->getId());
    }
}