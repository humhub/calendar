<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\CalendarUnitTest;
use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\space\models\Space;
use Recurr\Frequency;

class RecurrenceFormTest extends CalendarUnitTest
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
     * @var RecurrenceFormModel
     */
    protected $form;

    private function initForm($rrule = null)
    {
        parent::_before();
        $this->becomeUser('Admin');
        $this->space = Space::findOne(['id' => 1]);
        $startDate = (new DateTime())->setDate(2019, 11, 10);
        $this->entry = $this->createEntry($startDate, 1, 'Past Entry', $this->space);
        $this->entry->updateAttributes(['rrule' => $rrule]);
        $this->form = new RecurrenceFormModel(['entry' => $this->entry]);
    }

    public function testDefaultValues()
    {
        $this->initForm();
        $this->assertEquals(1, $this->form->interval);
        $this->assertEquals(RecurrenceFormModel::FREQUENCY_NEVER, $this->form->frequency);
        $this->assertCount(1, $this->form->weekDays);
        $this->assertContains(CalendarUtils::DOW_SUNDAY, $this->form->weekDays);
        $this->assertEquals(RecurrenceFormModel::MONTHLY_BY_DAY_OF_MONTH, $this->form->monthDay);
        $this->assertEquals(RecurrenceFormModel::ENDS_NEVER, $this->form->end);
        $this->assertTrue($this->form->validate());
    }

    public function testLoadWeeklyRule()
    {
        $this->initForm();
        $this->form->load([
                'interval' => 2,
                'frequency' => Frequency::WEEKLY,
                'weekDays' => [CalendarUtils::DOW_SUNDAY, CalendarUtils::DOW_MONDAY]

        ], '');

        $this->assertTrue($this->form->save());
        $this->assertEquals('FREQ=WEEKLY;INTERVAL=2;BYDAY=SU,MO', $this->entry->rrule);
    }

    public function testEditWeeklyRule()
    {
        $this->initForm( 'FREQ=WEEKLY;INTERVAL=2;BYDAY=SU,MO');
        $this->assertEquals(2, $this->form->interval);
        $this->assertEquals(Frequency::WEEKLY, $this->form->frequency);
        $this->assertCount(2, $this->form->weekDays);
        $this->assertContains(CalendarUtils::DOW_SUNDAY, $this->form->weekDays);
        $this->assertContains(CalendarUtils::DOW_MONDAY, $this->form->weekDays);
        $this->assertTrue($this->form->validate());
    }

    public function testLoadMonthlyRuleByDayOfMonth()
    {
        $this->initForm();
        $this->form->load([
            'interval' => 2,
            'frequency' => Frequency::MONTHLY,
            'monthDay' => RecurrenceFormModel::MONTHLY_BY_DAY_OF_MONTH
        ], '');

        $this->assertTrue($this->form->save());
        $this->assertEquals('FREQ=MONTHLY;INTERVAL=2;BYMONTHDAY=10', $this->entry->rrule);
    }

    public function testEditMonthlyRuleByDayOfMonth()
    {
        $this->initForm('FREQ=MONTHLY;INTERVAL=2;BYMONTHDAY=10');
        $this->assertEquals(2, $this->form->interval);
        $this->assertEquals(Frequency::MONTHLY, $this->form->frequency);
        $this->assertEquals(RecurrenceFormModel::MONTHLY_BY_DAY_OF_MONTH, $this->form->monthDay);
        $this->assertTrue($this->form->validate());
    }

    public function testLoadMonthlyRuleByOccurrence()
    {
        $this->initForm();
        $this->form->load([
            'interval' => 2,
            'frequency' => Frequency::MONTHLY,
            'monthDay' => RecurrenceFormModel::MONTHLY_BY_OCCURRENCE,
            'weekDays' => [CalendarUtils::DOW_SUNDAY, CalendarUtils::DOW_MONDAY] // SHOULD BE IGNORED
        ], '');

        $this->assertTrue($this->form->save());
        $this->assertEquals('FREQ=MONTHLY;INTERVAL=2;BYDAY=SU;BYSETPOS=2', $this->entry->rrule);
    }

    public function testEditMonthlyRuleByOccurrence()
    {
        $this->initForm('FREQ=MONTHLY;INTERVAL=3;BYDAY=SU;BYSETPOS=2');
        $this->assertEquals(3, $this->form->interval);
        $this->assertEquals(Frequency::MONTHLY, $this->form->frequency);
        $this->assertEquals(RecurrenceFormModel::MONTHLY_BY_OCCURRENCE, $this->form->monthDay);
        $this->assertTrue($this->form->validate());
    }
}