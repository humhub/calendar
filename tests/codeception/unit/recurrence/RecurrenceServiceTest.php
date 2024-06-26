<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\RecurrenceUnitTest;
use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\event\AbstractCalendarQuery;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;

class RecurrenceServiceTest extends RecurrenceUnitTest
{
    /**
     * @var CalendarService
     */
    public $service;

    public function _before()
    {
        parent::_before();
        $this->service = new CalendarService();
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function testGetUpcommingEntries()
    {
        $this->becomeUser('Admin');
        $this->space = Space::findOne(['id' => 1]);

        $today = (new DateTime())->setTime(0, 0, 0);

        $this->rootEvent = $this->createEntry($today, 1, 'Recurrent Event', $this->space);
        $this->setDefaults($this->rootEvent, 'FREQ=DAILY;INTERVAL=1');
        $this->assertTrue($this->rootEvent->saveEvent());

        $recurrences = $this->service->getUpcomingEntries(null, 6, 20);

        $this->assertCount(7, $recurrences);
        $this->assertEquals($recurrences[0]->start_datetime, CalendarUtils::toDBDateFormat($today));
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+1 day')), $recurrences[1]->start_datetime);
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+2 day')), $recurrences[2]->start_datetime);
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+3 day')), $recurrences[3]->start_datetime);
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+4 day')), $recurrences[4]->start_datetime);
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+5 day')), $recurrences[5]->start_datetime);

        foreach ($recurrences as $recurrence) {
            $this->assertFalse(RecurrenceHelper::isRecurrentRoot($recurrence));
        }
    }

    public function testGetAndSaveUpcommingEntries()
    {
        $this->becomeUser('Admin');
        $this->space = Space::findOne(['id' => 1]);

        $today = (new DateTime())->setTime(0, 0, 0);

        $this->rootEvent = $this->createEntry($today, 1, 'Recurrent Event', $this->space);
        $this->setDefaults($this->rootEvent, 'FREQ=DAILY;INTERVAL=1');
        $this->assertTrue($this->rootEvent->saveEvent());

        /* @var $recurrences RecurrentEventIF[] */
        $recurrences = $this->service->getUpcomingEntries(null, 6, 20);

        $this->assertCount(7, $recurrences);

        $this->assertEquals(0, $this->rootEvent->getRecurrenceInstances()->count());

        foreach ($recurrences as $recurrence) {
            $recurrence = CalendarUtils::getCalendarEvent($recurrence);
            $this->assertTrue($recurrence->getContentRecord()->isNewRecord);
            $recurrence->saveEvent();
        }

        $this->assertEquals(7, $this->rootEvent->getRecurrenceInstances()->count());

        $this->assertCount(8, CalendarEntry::find()->all());

        // Refetch upcoming events in order to test if instances are equal

        /* @var $recurrencesNew CalendarEntry[] */
        $recurrencesNew = $this->service->getUpcomingEntries(null, 6, 20);

        $this->assertCount(7, $recurrencesNew);

        $count = 0;
        foreach ($recurrencesNew as $recurrence) {
            $recurrence = CalendarUtils::getCalendarEvent($recurrence);
            $this->assertFalse($recurrence->getContentRecord()->isNewRecord);
            $this->assertEquals($recurrencesNew[$count]->id, $recurrences[$count]->id);
            $count++;
        }

        $this->assertEquals(7, $this->rootEvent->getRecurrenceInstances()->count());

        $this->assertCount(8, CalendarEntry::find()->all());
        $this->assertCount(8, Content::find()->where(['object_model' => CalendarEntry::class])->all());
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function testGetEntriesFilteredByAttending()
    {
        $admin = $this->becomeUser('Admin');
        $this->space = Space::findOne(['id' => 1]);

        $today = (new DateTime())->setTime(0, 0, 0);

        $this->rootEvent = $this->createEntry($today, 1, 'Recurrent Event', $this->space);
        $this->setDefaults($this->rootEvent, 'FREQ=DAILY;INTERVAL=1');
        $this->assertTrue($this->rootEvent->saveEvent());

        /* @var CalendarEntry[] $recurrences */
        $recurrences = $this->service->getUpcomingEntries(null, 6, 20);

        $this->assertCount(7, $recurrences);
        $this->assertEquals($recurrences[0]->start_datetime, CalendarUtils::toDBDateFormat($today));
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+1 day')), $recurrences[1]->start_datetime);
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+2 day')), $recurrences[2]->start_datetime);
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+3 day')), $recurrences[3]->start_datetime);
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+4 day')), $recurrences[4]->start_datetime);
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+5 day')), $recurrences[5]->start_datetime);

        // Attend to this event
        $this->rootEvent->getRecurrenceQuery()
            ->expandSingle(CalendarUtils::cleanRecurrentId($recurrences[1]->start_datetime))
            ->setParticipationStatus($admin);
        // Don't attend to this event
        $this->rootEvent->getRecurrenceQuery()
            ->expandSingle(CalendarUtils::cleanRecurrentId($recurrences[2]->start_datetime));
        // Attend to this event
        $this->rootEvent->getRecurrenceQuery()
            ->expandSingle(CalendarUtils::cleanRecurrentId($recurrences[3]->start_datetime))
            ->setParticipationStatus($admin);

        /* @var CalendarEntry[] $filteredRecurrences */
        $filteredRecurrences = $this->service->getUpcomingEntries(null, 6, 20, [AbstractCalendarQuery::FILTER_PARTICIPATE], false);

        $this->assertCount(2, $filteredRecurrences);
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+1 day')), $filteredRecurrences[0]->start_datetime);
        $this->assertEquals(CalendarUtils::toDBDateFormat((clone $today)->modify('+3 day')), $filteredRecurrences[1]->start_datetime);
    }
}
