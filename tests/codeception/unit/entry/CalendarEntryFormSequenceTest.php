<?php


namespace humhub\modules\calendar\tests\codeception\unit\entry;


use calendar\CalendarUnitTest;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\space\models\Space;
use Recurr\Frequency;

class CalendarEntryFormSequenceTest extends CalendarUnitTest
{
    /**
     * @var CalendarEntryForm
     */
    private $form;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('User2');
        $space1 = Space::findOne(['id' => 1]);

        $this->form = CalendarEntryForm::createEntry($space1);

        $this->assertTrue($this->form->load([
            'CalendarEntry' => [
                'all_day' => '0',
                'title' => 'Test title',
                'description' => 'TestDescription',
                'participation_mode' => 2
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'start_date' => '6/27/17',
                'start_time' => '12:00 PM',
                'end_date' => '6/28/17',
                'end_time' => '01:00 PM'
            ]
        ]));

        $this->assertTrue($this->form->save());
    }

    public function testSequenceIncrementDate()
    {
        $this->assertEquals(0, $this->form->entry->sequence);
        $calendarForm = new CalendarEntryForm(['entry' => $this->form->entry]);
        $calendarForm->load([
            'CalendarEntry' => [
                'all_day' => '1'
            ],
            'CalendarEntryForm' => [
                'start_date' => '7/16/17',
                'end_date' => '8/16/17'
            ]
        ]);

        $this->assertTrue($calendarForm->save());
        $this->assertEquals(1, $this->form->entry->sequence);
    }

    public function testSequenceIncrementEndDate()
    {
        $this->assertEquals(0, $this->form->entry->sequence);
        $calendarForm = new CalendarEntryForm(['entry' => $this->form->entry]);
        $calendarForm->load([
            'CalendarEntry' => [
                'all_day' => '1'
            ],
            'CalendarEntryForm' => [
                'end_date' => '8/16/17'
            ]
        ]);

        $this->assertTrue($calendarForm->save());
        $this->assertEquals(1, $this->form->entry->sequence);
    }

    public function testSequenceIncrementRRule()
    {
        $this->assertEquals(0, $this->form->entry->sequence);
        $calendarForm = new CalendarEntryForm(['entry' => $this->form->entry]);
        $calendarForm->load([
            'CalendarEntry' => [],
            'CalendarEntryForm' => [],
            'RecurrenceFormModel' => [
                'frequency' => Frequency::DAILY,
                'interval' => 1
            ]
        ]);

        $this->assertTrue($calendarForm->save());
        $this->assertEquals(1, $this->form->entry->sequence);
    }

    public function testSequenceNotIncremented()
    {
        $this->assertEquals(0, $this->form->entry->sequence);
        $calendarForm = new CalendarEntryForm(['entry' => $this->form->entry]);
        $calendarForm->load([
            'CalendarEntry' => [],
            'CalendarEntryForm' => [
                'is_public' => '0'
            ],
        ]);

        $this->assertTrue($calendarForm->save());
        $this->assertEquals(0, $this->form->entry->sequence);
    }

}