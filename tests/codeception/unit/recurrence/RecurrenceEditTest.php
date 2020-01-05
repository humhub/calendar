<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\CalendarUnitTest;
use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\calendar\models\recurrence\CalendarRecurrenceExpand;
use humhub\modules\external_calendar\integration\calendar\CalendarExtension;
use humhub\modules\space\models\Space;
use Recurr\Frequency;
use Yii;

class RecurrenceEditTest extends CalendarUnitTest
{
    /**
     * @var Space
     */
    protected $space;

    /**
     * @var CalendarEntry
     */
    protected $rootEvent;

    /**
     * @var CalendarEntry[]
     */
    protected $recurrences;

    private function initRecurrentEvents($rrule = 'FREQ=DAILY', $startDate = null)
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->space = Space::findOne(['id' => 1]);
        $startDate = $startDate ?: $this->getEntryDate();
        $this->rootEvent = $this->createEntry($startDate, 1, 'Past Entry', $this->space);
        $this->setDefaults($this->rootEvent, $rrule);
        $this->assertTrue($this->rootEvent->save());
        $this->recurrences = $this->expand(true);
    }

    private function setDefaults(CalendarEntry $entry, $rrule = null)
    {
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $entry->title = 'My Recurrent Event';
        $entry->description = 'My Recurrent Event Description';
        $entry->setRrule($rrule);
    }

    private function expand( $save = false, $entry = null,  $fromDay = 1, $toDay = 7)
    {
        if(!$entry) {
            $entry = $this->rootEvent;
        }
        $expandStart = (new DateTime)->setDate(2019, 12, $fromDay);
        $expandEnd = (new DateTime)->setDate(2019, 12,  $toDay);
        $result = [];
        return CalendarRecurrenceExpand::expand($entry, $expandStart, $expandEnd, $result, $save);
    }

    private function getEntryDate()
    {
        return (new DateTime())->setDate(2019, 12, 1);
    }

    public function testInit()
    {
        $this->initRecurrentEvents();
        $this->assertCount(7, $this->recurrences);
        $this->assertEquals('2019-12-01 00:00:00', $this->recurrences[0]->start_datetime);
        $this->assertEquals('2019-12-07 00:00:00', end($this->recurrences)->start_datetime);
    }

    /**
     * Edit only one recurrence instnace which is not root, change date and data
     * @throws \Throwable
     */
    public function testEditThisEventOnNonRoot()
    {
        $this->initRecurrentEvents();
        $this->assertEquals('2019-12-02 00:00:00', $this->recurrences[1]->start_datetime);
        $form = new CalendarEntryForm(['entry' => $this->recurrences[1]]);
        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Overwritten title',
                'description' => 'Overwritten description',
                'participation_mode' => CalendarEntryParticipation::PARTICIPATION_MODE_NONE
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'all_day' => '1',
                'start_date' => '12/3/19',
                'start_time' => '10:00 AM',
                'end_date' => '12/3/19',
                'end_time' => '10:30 AM'
            ],
            'RecurrenceFormModel' => [
                'recurrenceEditMode' => RecurrenceFormModel::EDIT_MODE_THIS
            ]
        ]));

        $this->assertTrue($form->save());
        $this->assertEquals('2019-12-03 10:00:00', $form->entry->start_datetime);
        $this->assertEquals('2019-12-03 10:30:00', $form->entry->end_datetime);
        /* @var $newRecurrences CalendarEntry[] */
        $newRecurrences = $this->expand();

        $this->assertEquals('2019-12-01 00:00:00', $newRecurrences[0]->start_datetime);
        $this->assertEquals('My Recurrent Event', $newRecurrences[0]->getTitle());
        $this->assertEquals('My Recurrent Event Description', $newRecurrences[0]->getDescription());
        $this->assertEquals('FREQ=DAILY', $newRecurrences[1]->getRrule());
        $this->assertEquals(CalendarEntryParticipation::PARTICIPATION_MODE_ALL, $newRecurrences[0]->participation_mode);

        $this->assertEquals($form->entry->id, $newRecurrences[1]->id);
        $this->assertEquals('2019-12-03 10:00:00', $newRecurrences[1]->start_datetime);
        $this->assertEquals('Overwritten title', $newRecurrences[1]->getTitle());
        $this->assertEquals('Overwritten description', $newRecurrences[1]->getDescription());
        $this->assertEquals('FREQ=DAILY', $newRecurrences[1]->getRrule());
        $this->assertEquals(CalendarEntryParticipation::PARTICIPATION_MODE_NONE, $newRecurrences[1]->participation_mode);

        $this->assertEquals('2019-12-03 00:00:00', $newRecurrences[2]->start_datetime);
        $this->assertEquals('My Recurrent Event', $newRecurrences[2]->getTitle());
        $this->assertEquals('My Recurrent Event Description', $newRecurrences[2]->getDescription());
        $this->assertEquals('FREQ=DAILY', $newRecurrences[1]->getRrule());
        $this->assertEquals(CalendarEntryParticipation::PARTICIPATION_MODE_ALL, $newRecurrences[2]->participation_mode);

        //TODO: test edit topics
        //TODO: test ignore public
        //TODO: test edit calendar type
        //TODO: test reminder
        //TODO: test files
    }

    public function testEditThisEventOnRoot()
    {
        $this->initRecurrentEvents();
        $this->assertEquals('2019-12-01 00:00:00', $this->recurrences[0]->start_datetime);
        $form = new CalendarEntryForm(['entry' => $this->recurrences[0]]);
        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Overwritten title',
                'description' => 'Overwritten description',
                'participation_mode' => CalendarEntryParticipation::PARTICIPATION_MODE_NONE
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'all_day' => '1',
                'start_date' => '12/2/19',
                'start_time' => '10:00 AM',
                'end_date' => '12/2/19',
                'end_time' => '10:30 AM'
            ],
            'RecurrenceFormModel' => [
                'recurrenceEditMode' => RecurrenceFormModel::EDIT_MODE_THIS
            ]
        ]));

        $this->assertTrue($form->save());

        $this->assertEquals('2019-12-02 10:00:00', $form->entry->start_datetime);
        $this->assertEquals('2019-12-02 10:30:00', $form->entry->end_datetime);
        /* @var $newRecurrences CalendarEntry[] */
        $newRecurrences = $this->expand();

        $this->assertEquals($form->entry->id, $newRecurrences[0]->id);
        $this->assertEquals('2019-12-02 10:00:00', $newRecurrences[0]->start_datetime);
        $this->assertEquals('Overwritten title', $newRecurrences[0]->getTitle());
        $this->assertEquals('Overwritten description', $newRecurrences[0]->getDescription());
        $this->assertEquals('FREQ=DAILY', $newRecurrences[0]->getRrule());
        $this->assertEquals(CalendarEntryParticipation::PARTICIPATION_MODE_NONE, $newRecurrences[0]->participation_mode);

        $this->assertEquals('2019-12-02 00:00:00', $newRecurrences[1]->start_datetime);
        $this->assertEquals('My Recurrent Event', $newRecurrences[1]->getTitle());
        $this->assertEquals('My Recurrent Event Description', $newRecurrences[1]->getDescription());
        $this->assertEquals('FREQ=DAILY', $newRecurrences[1]->getRrule());
        $this->assertEquals(CalendarEntryParticipation::PARTICIPATION_MODE_ALL, $newRecurrences[1]->participation_mode);
    }

    public function testEditFollowingEventsOnNonRootChangeRecurrence()
    {
        $this->initRecurrentEvents();
        $form = new CalendarEntryForm(['entry' => $this->recurrences[2]]);
        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Overwritten title',
                'description' => 'Overwritten description',
                'participation_mode' => CalendarEntryParticipation::PARTICIPATION_MODE_NONE
            ],
            'CalendarEntryForm' => [
                'is_public' => '0',
                'all_day' => '1',
                'start_date' => '12/3/19',
                'end_date' => '12/3/19'
            ],
            'RecurrenceFormModel' => [
                'recurrenceEditMode' => RecurrenceFormModel::EDIT_MODE_FOLLOWING
            ]
        ]));

        $this->assertTrue($form->save());
        $this->rootEvent->refresh();
        $newRecurrences = $this->expand();
        $this->assertCount(2, $newRecurrences);
        $this->assertEquals('2019-12-01 00:00:00', $newRecurrences[0]->start_datetime);
        $this->assertEquals('2019-12-02 00:00:00', $newRecurrences[1]->start_datetime);

        // Test other instances
    }

    public function testEditFollowingEventsOnRootChangeRecurrence()
    {
        $this->initRecurrentEvents();
        $form = new CalendarEntryForm(['entry' => $this->recurrences[0]]);
        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Overwritten title',
                'description' => 'Overwritten description',
                'participation_mode' => CalendarEntryParticipation::PARTICIPATION_MODE_NONE
            ],
            'CalendarEntryForm' => [
                'is_public' => '0',
                'all_day' => '1',
                'start_date' => '12/4/19',
                'end_date' => '12/4/19'
            ],
            'RecurrenceFormModel' => [
                'recurrenceEditMode' => RecurrenceFormModel::EDIT_MODE_FOLLOWING
            ]
        ]));

        $this->assertTrue($form->save());

        // The old root should be deleted
        $this->assertNull(CalendarEntry::findOne(['id' => $this->rootEvent->id]));
        $this->assertTrue(RecurrenceHelper::isRecurrent($form->entry));
        $this->assertTrue(RecurrenceHelper::isRecurrentRoot($form->entry));
        $this->assertFalse(RecurrenceHelper::isRecurrentInstance($form->entry));

        $newRecurrences = $this->expand(false, $form->entry, 1, 7);
        $this->assertCount(4, $newRecurrences);
        $this->assertEquals('2019-12-04 00:00:00', $newRecurrences[0]->start_datetime);
        $this->assertEquals('2019-12-05 00:00:00', $newRecurrences[1]->start_datetime);
    }

    public function testEditAllEvents()
    {
        $this->initRecurrentEvents();
        $form = new CalendarEntryForm(['entry' => $this->rootEvent]);
        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Overwritten title',
                'description' => 'Overwritten description',
                'participation_mode' => CalendarEntryParticipation::PARTICIPATION_MODE_NONE
            ],
            'CalendarEntryForm' => [
                'is_public' => '0',
                'all_day' => '1',
                'start_date' => '12/4/19',
                'end_date' => '12/4/19'
            ],
            'RecurrenceFormModel' => [
                'recurrenceEditMode' => RecurrenceFormModel::EDIT_MODE_ALL,
                'frequency' => Frequency::DAILY,
                'interval' => 2
            ]
        ]));

        $form->save();

        $this->assertTrue($form->save());

        $this->assertNotNull(CalendarEntry::findOne(['id' => $this->rootEvent->id]));

        // Check old recurrences (just for clarity)
        $this->assertEquals('2019-12-01 00:00:00', $this->recurrences[0]->start_datetime);
        $this->assertEquals('2019-12-02 00:00:00', $this->recurrences[1]->start_datetime);
        $this->assertEquals('2019-12-03 00:00:00', $this->recurrences[2]->start_datetime);
        $this->assertEquals('2019-12-04 00:00:00', $this->recurrences[3]->start_datetime);
        $this->assertEquals('2019-12-05 00:00:00', $this->recurrences[4]->start_datetime);
        $this->assertEquals('2019-12-06 00:00:00', $this->recurrences[5]->start_datetime);
        $this->assertEquals('2019-12-07 00:00:00', $this->recurrences[6]->start_datetime);

        // Make sure recurrences which are still valid were not deleted
        $this->assertNull(CalendarEntry::findOne(['id' => $this->recurrences[0]->id]));
        $this->assertNull(CalendarEntry::findOne(['id' => $this->recurrences[1]->id]));
        $this->assertNull(CalendarEntry::findOne(['id' => $this->recurrences[2]->id]));
        $this->assertNotNull(CalendarEntry::findOne(['id' => $this->recurrences[3]->id]));
        $this->assertNull(CalendarEntry::findOne(['id' => $this->recurrences[4]->id]));
        $this->assertNotNull(CalendarEntry::findOne(['id' => $this->recurrences[5]->id]));
        $this->assertNull(CalendarEntry::findOne(['id' => $this->recurrences[6]->id]));

        $newRecurrences = $this->expand();
        $this->assertCount(2, $newRecurrences);
        $this->assertEquals('2019-12-04 00:00:00', $newRecurrences[0]->start_datetime);
        $this->assertEquals('2019-12-06 00:00:00', $newRecurrences[1]->start_datetime);
    }


    //TODO: test edit participation mode
}