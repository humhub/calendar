<?php


namespace humhub\modules\calendar\interfaces\recurrence;

use humhub\modules\calendar\helpers\CalendarUtils;
use Recurr\Rule;
use Yii;
use yii\base\Model;

class RecurrenceFormModel extends Model
{
    const INTERVAL_DAILY = 1;
    const INTERVAL_WEEKLY = 2;
    const INTERVAL_MONTHLY = 3;
    const INTERVAL_YEARLY = 4;

    /**
     * @var RecurrentCalendarEntry
     */
    public $entry;

    public $repeatIntervalValue = 1;

    public $repeatIntervalType = self::INTERVAL_WEEKLY;

    public $weekDays;

    public $monthDaySelection;

    /**
     * @var Rule
     */
    private $rrule;

    public function init()
    {
        parent::init();
        $this->weekDays = [$this->getDayOfWeek()];
    }

    /**
     * @return Rule
     * @throws \Recurr\Exception\InvalidRRule
     */
    public function getRrule()
    {
        if(!$this->rrule) {
            $this->rrule = new Rule($this->entry->getRrule());
        }

        return $this->rrule;
    }

    public function getMonthlyDaySelection()
    {
        return  [
            1 => Yii::t('CalendarModule.recurrence','Monthly on day {dayOfMonth}', [
                'dayOfMonth' => $this->getDayOfMonth()
            ]),
            2 => Yii::t('CalendarModule.recurrence','Monthly on the {position} {dayOfWeek}', [
                'position' => $this->getMonthlyPosition(),
                'dayOfWeek' => $this->getDayOfWeekFormatted()
            ]),
        ];
    }

    public function getIntervalTypesSelection() {
        return [
            static::INTERVAL_DAILY => Yii::t('CalendarModule.recurrence','Daily'),
            static::INTERVAL_WEEKLY => Yii::t('CalendarModule.recurrence','Weekly'),
            static::INTERVAL_MONTHLY => Yii::t('CalendarModule.recurrence','Monthly'),
            static::INTERVAL_YEARLY => Yii::t('CalendarModule.recurrence','Yearly'),
        ];
    }

    /**
     * @return int
     */
    private function getDayOfWeek()
    {
        $map = [
            'mon' => CalendarUtils::DOW_MONDAY,
            'tue' => CalendarUtils::DOW_TUESDAY,
            'wed' => CalendarUtils::DOW_WEDNESDAY,
            'thu' => CalendarUtils::DOW_THURSDAY,
            'fri' => CalendarUtils::DOW_FRIDAY,
            'sat' => CalendarUtils::DOW_SATURDAY,
            'sun' => CalendarUtils::DOW_SUNDAY,
        ];
        
        return $map[strtolower($this->entry->getStartDateTime()->format('D'))];
    }

    private function getMonthlyPosition()
    {
        $dayNum = strtolower($this->getDayOfMonth());
        $position = floor(($dayNum - 1) / 7) + 1;

        switch ($position) {
            case 2:
                return Yii::t('CalendarModule.recurrence','second');
            case 3:
                return Yii::t('CalendarModule.recurrence','third');
            case 4:
                return Yii::t('CalendarModule.recurrence','forth');
            default:
                return Yii::t('CalendarModule.recurrence','first');
        }
    }

    public function getEndTypeSelection()
    {
        return [
            1 => Yii::t('CalendarModule.recurrence','Never'),
            2 => Yii::t('CalendarModule.recurrence','On date'),
            3 => Yii::t('CalendarModule.recurrence','After (occurrences)'),
        ];
    }

    private function getDayOfWeekFormatted()
    {
        return CalendarUtils::getDayOfWeek($this->getDayOfWeek());
    }

    private function getDayOfMonth()
    {
        return $this->entry->getStartDateTime()->format('d');
    }
}