<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\CalendarUnitTest;
use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\space\models\Space;
use Recurr\Frequency;
use Yii;

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
        $startDate = $this->getEntryDate();
        $this->entry = $this->createEntry($startDate, 1, 'Past Entry', $this->space);
        $this->entry->updateAttributes(['rrule' => $rrule]);
        $this->form = new RecurrenceFormModel(['entry' => $this->entry]);
    }

    private function getEntryDate()
    {
        return (new DateTime())->setDate(2019, 11, 10);
    }

    public function testDefaultValues()
    {
        $this->initForm();
        $this->assertEquals(1, $this->form->interval);
        $this->assertEquals(RecurrenceFormModel::FREQUENCY_NEVER, $this->form->frequency);
        $this->assertCount(1, $this->form->weekDays);
        $this->assertContains(CalendarUtils::DOW_SUNDAY, $this->form->weekDays);
        $this->assertEquals(RecurrenceFormModel::MONTHLY_BY_DAY_OF_MONTH, $this->form->monthDaySelection);
        $this->assertEquals(RecurrenceFormModel::ENDS_NEVER, $this->form->end);
        $this->assertEquals('2019-11-17 00:00:00', $this->form->endDate);
        $this->assertEquals(10, $this->form->endOccurrences);
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
            'monthDaySelection' => RecurrenceFormModel::MONTHLY_BY_DAY_OF_MONTH
        ], '');

        $this->assertTrue($this->form->save());
        $this->assertEquals('FREQ=MONTHLY;INTERVAL=2;BYMONTHDAY=10', $this->entry->rrule);
    }

    public function testEditMonthlyRuleByDayOfMonth()
    {
        $this->initForm('FREQ=MONTHLY;INTERVAL=2;BYMONTHDAY=10');
        $this->assertEquals(2, $this->form->interval);
        $this->assertEquals(Frequency::MONTHLY, $this->form->frequency);
        $this->assertEquals(RecurrenceFormModel::MONTHLY_BY_DAY_OF_MONTH, $this->form->monthDaySelection);
        $this->assertTrue($this->form->validate());
    }

    public function testLoadMonthlyRuleByOccurrence()
    {
        $this->initForm();
        $this->form->load([
            'interval' => 2,
            'frequency' => Frequency::MONTHLY,
            'monthDaySelection' => RecurrenceFormModel::MONTHLY_BY_OCCURRENCE,
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
        $this->assertEquals(RecurrenceFormModel::MONTHLY_BY_OCCURRENCE, $this->form->monthDaySelection);
        $this->assertTrue($this->form->validate());
    }

    public function testLoadRuleWithEndByDate()
    {
        $this->initForm();
        $this->form->load([
            'interval' => 2,
            'frequency' => Frequency::MONTHLY,
            'monthDaySelection' => RecurrenceFormModel::MONTHLY_BY_OCCURRENCE,
            'end' => RecurrenceFormModel::ENDS_ON_DATE,
            'endDate' => '12/11/22'
        ], '');

        $this->assertTrue($this->form->save());
        $this->assertEquals('FREQ=MONTHLY;UNTIL=20221211T000000Z;INTERVAL=2;BYDAY=SU;BYSETPOS=2', $this->entry->rrule);
    }

    public function testEditRuleWithEndByDate()
    {
        $this->initForm('FREQ=MONTHLY;UNTIL=20221211T000000Z;INTERVAL=2;BYDAY=SU;BYSETPOS=2');
        $this->assertEquals(2, $this->form->interval);
        $this->assertEquals(Frequency::MONTHLY, $this->form->frequency);
        $this->assertEquals(RecurrenceFormModel::MONTHLY_BY_OCCURRENCE, $this->form->monthDaySelection);
        $this->assertEquals(RecurrenceFormModel::ENDS_ON_DATE, $this->form->end);
        $this->assertEquals('2022-12-11 00:00:00', $this->form->endDate);
        $this->assertTrue($this->form->validate());
    }

    public function testLoadRuleWithEndByOccurrence()
    {
        $this->initForm();
        $this->form->load([
            'interval' => 2,
            'frequency' => Frequency::MONTHLY,
            'monthDaySelection' => RecurrenceFormModel::MONTHLY_BY_OCCURRENCE,
            'end' => RecurrenceFormModel::ENDS_AFTER_OCCURRENCES,
            'endOccurrences' => 20
        ], '');

        $this->assertTrue($this->form->save());
        $this->assertEquals('FREQ=MONTHLY;COUNT=20;INTERVAL=2;BYDAY=SU;BYSETPOS=2', $this->entry->rrule);
    }

    public function testEditRuleWithEndByOccurrence()
    {
        $this->initForm('FREQ=MONTHLY;COUNT=20;INTERVAL=2;BYDAY=SU;BYSETPOS=2');
        $this->assertEquals(2, $this->form->interval);
        $this->assertEquals(Frequency::MONTHLY, $this->form->frequency);
        $this->assertEquals(RecurrenceFormModel::MONTHLY_BY_OCCURRENCE, $this->form->monthDaySelection);
        $this->assertEquals(RecurrenceFormModel::ENDS_AFTER_OCCURRENCES, $this->form->end);
        $this->assertEquals(20, $this->form->endOccurrences);
        $this->assertTrue($this->form->validate());
    }
}