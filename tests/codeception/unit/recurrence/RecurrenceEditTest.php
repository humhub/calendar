<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\CalendarUnitTest;
use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\interfaces\recurrence\RecurrentCalendarEventIF;
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
    protected $entry;

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
        $this->entry = $this->createEntry($startDate, 1, 'Past Entry', $this->space);
        $this->setDefaults($this->entry, $rrule);
        $this->assertTrue($this->entry->save());
        $this->recurrences = $this->expand(true);
    }

    private function setDefaults(CalendarEntry $entry, $rrule = null)
    {
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $entry->title = 'My Recurrent Event';
        $entry->description = 'My Recurrent Event Description';
        $entry->setRrule($rrule);
    }

    private function expand( $save = false, $entry = null, &$result = [], $fromDay = 1, $toDay = 7)
    {
        if(!$entry) {
            $entry = $this->entry;
        }
        $expandStart = (new DateTime)->setDate(2019, 12, $fromDay);
        $expandEnd = (new DateTime)->setDate(2019, 12,  $toDay);
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
        $this->entry->refresh();
        $newRecurrences = $this->expand();
        $this->assertCount(2, $newRecurrences);
        $this->assertEquals('2019-12-01 00:00:00', $newRecurrences[0]->start_datetime);
        $this->assertEquals('2019-12-02 00:00:00', $newRecurrences[1]->start_datetime);

        // Test other instances
    }


    //TODO: test edit participation mode
}