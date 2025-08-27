<?php

namespace humhub\modules\calendar\interfaces\recurrence;

use humhub\libs\DbDateValidator;
use humhub\modules\calendar\helpers\CalendarUtils;
use DateTime;
use humhub\modules\calendar\helpers\RRuleHelper;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use Recurr\Exception\InvalidArgument;
use Recurr\Exception\InvalidRRule;
use Recurr\Frequency;
use Recurr\Rule;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\web\HttpException;

class RecurrenceFormModel extends Model
{
    public const EDIT_MODE_CREATE = 0;
    public const EDIT_MODE_THIS = 1;
    public const EDIT_MODE_FOLLOWING = 2;
    public const EDIT_MODE_ALL = 3;

    public const FREQUENCY_NEVER = -1;

    public const MONTHLY_BY_DAY_OF_MONTH = 1;
    public const MONTHLY_BY_OCCURRENCE = 2;
    public const MONTHLY_LAST_DAY_OF_MONTH = 3;

    public const ENDS_NEVER = 0;
    public const ENDS_ON_DATE = 1;
    public const ENDS_AFTER_OCCURRENCES = 2;

    /**
     * @var RecurrentEventIF
     */
    public $entry;

    public $interval = 1;

    public $frequency = self::FREQUENCY_NEVER;

    public $weekDays;

    public $monthDaySelection = RecurrenceFormModel::MONTHLY_BY_DAY_OF_MONTH;

    public $end = self::ENDS_NEVER;

    public $endDate;

    public $endOccurrences = 10;

    /**
     * @var int
     */
    public $recurrenceEditMode;

    private $dayOfWeekMap = [
        CalendarUtils::DOW_SUNDAY => 'SU',
        CalendarUtils::DOW_MONDAY => 'MO',
        CalendarUtils::DOW_TUESDAY => 'TU',
        CalendarUtils::DOW_WEDNESDAY => 'WE',
        CalendarUtils::DOW_THURSDAY => 'TH',
        CalendarUtils::DOW_FRIDAY => 'FR',
        CalendarUtils::DOW_SATURDAY => 'SA',
    ];

    /**
     * @var Rule
     */
    private $rrule;

    /**
     * @throws InvalidRRule
     */
    public function init()
    {
        parent::init();
        $this->initRrule($this->entry->getRrule());
        if (RecurrenceHelper::isRecurrentRoot($this->entry)) {
            // Force edit mode all on root events
            $this->recurrenceEditMode = static::EDIT_MODE_ALL;
        }
    }

    private function getMonthDaySelectionByRRule(Rule $rule)
    {
        if ((int) $this->frequency !== Frequency::MONTHLY) {
            return static::MONTHLY_BY_DAY_OF_MONTH;
        }

        if ($rule->getBySetPosition() !== null) {
            return static::MONTHLY_BY_OCCURRENCE;
        }

        $byDay = $rule->getByDay();

        if ($byDay !== null && $byDay[0] = '-') {
            return static::MONTHLY_LAST_DAY_OF_MONTH;
        }

        return static::MONTHLY_BY_DAY_OF_MONTH;
    }

    /**
     * @param null $rruleStr
     * @return $this
     * @throws InvalidRRule
     * @throws \Exception
     */
    private function initRrule($rruleStr = null)
    {
        $this->rrule = new Rule($rruleStr);

        if ($rruleStr) {
            $this->interval = $this->rrule->getInterval();
            $this->frequency = $this->rrule->getFreq();
            $this->weekDays = $this->rrule->getByDay();

            $this->monthDaySelection = $this->getMonthDaySelectionByRRule($this->rrule);

            if ($this->frequency === Frequency::WEEKLY) {
                $byDays = $this->rrule->getByDay();
                if (is_array($byDays)) {
                    $dowMap = array_flip($this->dayOfWeekMap);
                    $this->weekDays = [];
                    foreach ($byDays as $day) {
                        if (isset($dowMap[$day])) {
                            $this->weekDays[] = $dowMap[$day];
                        }
                    }
                }
            }

            if ($this->rrule->getUntil()) {
                $this->end = static::ENDS_ON_DATE;
                $endDate = $this->rrule->getUntil();
                $endDate->setTimeZone(CalendarUtils::getStartTimeZone($this->entry));
                $this->setEndDate($endDate);
            } elseif ($this->rrule->getCount()) {
                $this->end = static::ENDS_AFTER_OCCURRENCES;
                $this->endOccurrences = $this->rrule->getCount();
            }
        }

        if (empty($this->weekDays)) {
            $this->weekDays = [$this->getStartDayOfWeek()];
        }

        if (empty($this->endDate)) {
            $endDate = clone $this->entry->getStartDateTime();
            $this->setEndDate($endDate->modify('+1 week')->setTime(0, 0));
        }

        return $this;
    }

    public function setEndDate($endDate)
    {
        if (is_string($endDate)) {
            $this->endDate = $endDate;
        } elseif ($endDate instanceof \DateTimeInterface) {
            $this->endDate = $endDate->format(CalendarUtils::DB_DATE_FORMAT);
        }
    }

    public function rules()
    {
        return [
            ['interval', 'integer', 'min' => 1],
            ['weekDays', 'safe'], //TODO: better validation
            ['frequency', 'integer', 'min' => static::FREQUENCY_NEVER, 'max' => Frequency::DAILY],
            ['monthDaySelection', 'integer', 'min' => static::MONTHLY_BY_DAY_OF_MONTH, 'max' => static::MONTHLY_LAST_DAY_OF_MONTH],
            ['frequency', 'validateFrequency'],
            ['frequency', 'validateModel'],
            ['end', 'integer', 'min' => static::ENDS_NEVER, 'max' => static::ENDS_AFTER_OCCURRENCES],
            ['endOccurrences', 'integer'],
            ['endDate', DbDateValidator::class, 'timeZone' => CalendarUtils::getStartTimeZone($this->entry)],
            ['recurrenceEditMode', 'integer', 'min'  => static::EDIT_MODE_THIS, 'max' => static::EDIT_MODE_ALL],
        ];
    }

    public function attributeLabels()
    {
        return [
            'frequency' => Yii::t('CalendarModule.base', 'Repeat every'),
            'weekDays' => Yii::t('CalendarModule.base', 'on weekdays'),
            'end' => Yii::t('CalendarModule.base', 'End'),
        ];
    }

    public function validateModel($attribute, $params)
    {
        if ($this->recurrenceEditMode === static::EDIT_MODE_ALL && !RecurrenceHelper::isRecurrentRoot($this->entry)) {
            // Currently the edit mode all is only valid if the root event itself is given due to load complexity...
            throw new HttpException(400, 'No root event given for edit mode all!');
        }

        if (!($this->entry instanceof RecurrentEventIF)) {
            $this->addError('frequency', Yii::t('CalendarModule.base', 'This event does not support recurrent events'));
        }
    }

    /**
     * @param $attribute
     * @param $params
     * @return |null
     * @throws InvalidRRule
     */
    public function validateFrequency($attribute, $params)
    {
        if ($this->frequency == static::FREQUENCY_NEVER) {
            return null;
        }

        $this->initRrule();

        try {
            $this->setRuleInterval();
        } catch (\Exception $e) {
            $this->addError('interval', Yii::t('CalendarModule.base', 'Invalid interval given'));
        }

        try {
            $this->setRuleFrequency();
        } catch (\Exception $e) {
            $this->addError('frequency', Yii::t('CalendarModule.base', 'Invalid frequency given'));
        }

        try {
            $this->setRuleDay();

            if ($this->frequency == Frequency::WEEKLY && empty($this->weekDays)) {
                $this->weekDays = [$this->getStartDayOfWeek()];
            }

        } catch (\Exception $e) {
            if ($this->interval == Frequency::MONTHLY) {
                $this->addError('monthDaySelection', Yii::t('CalendarModule.base', 'Invalid day of month given'));
            } elseif ($this->interval == Frequency::WEEKLY) {
                $this->addError('weekDays', Yii::t('CalendarModule.base', 'Invalid week day selection'));
            }
        }
    }

    /**
     * @param RecurrentEventIF|null $original
     * @return bool|void
     * @throws InvalidArgument
     * @throws InvalidRRule
     * @throws \Throwable
     */
    public function save(RecurrentEventIF $original = null)
    {
        if (!$this->validate()) {
            return false;
        }

        if (!$this->entry->getUid()) {
            $this->entry->setUid(CalendarUtils::generateEventUid($this->entry));
            $this->entry->saveEvent();
        }

        switch ($this->recurrenceEditMode) {
            case static::EDIT_MODE_THIS:
                // We only want to save this instance, so we ignore rrule changes
                return true;
            case static::EDIT_MODE_CREATE:
                $this->entry->setRrule($this->buildRRuleString());
                return $this->entry->saveEvent();
            case static::EDIT_MODE_FOLLOWING:
                $this->entry->setRrule($this->buildRRuleString());
                return $original ? $this->entry->getRecurrenceQuery()->saveThisAndFollowing($original) : false;
            case static::EDIT_MODE_ALL:
                $this->entry->setRrule($this->buildRRuleString());
                return $original ? $this->entry->getRecurrenceQuery()->saveAll($original) : false;
            default:
                $this->entry->saveEvent();
        }

        return true;
    }

    /**
     * @return string|null
     * @throws InvalidArgument
     * @throws InvalidRRule
     */
    public function buildRRuleString()
    {
        if ($this->frequency == static::FREQUENCY_NEVER) {
            return null;
        }

        return $this->initRrule()
            ->setRuleInterval()
            ->setRuleFrequency()
            ->setRuleDay()
            ->setRuleEnd()
            ->getRruleString();
    }


    /**
     * @return $this
     * @throws \Exception
     */
    private function setRuleEnd()
    {
        if ($this->end == static::ENDS_ON_DATE) {
            $until = $this->endDate instanceof \DateTimeInterface
                ? $this->endDate
                : new DateTime($this->endDate, CalendarUtils::getStartTimeZone($this->entry));
            $entryDateTime = $this->entry->getStartDateTime();
            $until->setTime($entryDateTime->format('H'), $entryDateTime->format('i'));
            $this->rrule->setUntil($until);
        } elseif ($this->end == static::ENDS_AFTER_OCCURRENCES) {
            $this->rrule->setCount($this->endOccurrences);
        }

        return $this;
    }

    /**
     * @param Rule $rrule
     * @return RecurrenceFormModel
     * @throws InvalidArgument
     */
    private function setRuleInterval()
    {
        $this->rrule->setInterval((int)$this->interval);
        return $this;
    }

    /**
     * @param Rule $rrule
     * @return RecurrenceFormModel
     * @throws InvalidArgument
     */
    private function setRuleFrequency()
    {
        if ($this->frequency !== static::FREQUENCY_NEVER) {
            $this->rrule->setFreq((int)$this->frequency);
        }

        return $this;
    }

    private function getRruleString()
    {
        return $this->rrule->getString(Rule::TZ_FIXED);
    }

    /**
     * @param Rule $rrule
     * @return RecurrenceFormModel
     * @throws InvalidRRule
     */
    private function setRuleDay()
    {
        if ($this->frequency == Frequency::WEEKLY) {
            $this->rrule->setByDay($this->getByDays());
        } elseif ($this->frequency == Frequency::MONTHLY) {
            if ($this->monthDaySelection == static::MONTHLY_BY_OCCURRENCE) {
                $pos = $this->getMonthlyPositionOfStart();
                $this->rrule->setByDay([$this->translateDayOfWeekToRrule($this->getStartDayOfWeek())]);
                $this->rrule->setBySetPosition([$pos]);
            } elseif ($this->monthDaySelection == static::MONTHLY_LAST_DAY_OF_MONTH) {
                $this->rrule->setByDay(['-1' . $this->translateDayOfWeekToRrule($this->getStartDayOfWeek())]);
            } elseif ($this->monthDaySelection == static::MONTHLY_BY_DAY_OF_MONTH) {
                $this->rrule->setByMonthDay([$this->getStartDayOfMonth()]);
            }
        }

        return $this;
    }

    private function isFifthWeekOfMonth($pos)
    {
        return $pos === 5;
    }

    private function getByDays()
    {
        $result = [];
        foreach ($this->weekDays as $dayOfWeek) {
            $dowMapping = $this->translateDayOfWeekToRrule($dayOfWeek);
            if ($dowMapping && !in_array($dowMapping, $result, true)) {
                $result[] = $dowMapping;
            }
        }

        return $result;
    }

    private function translateDayOfWeekToRrule($dow)
    {
        return isset($this->dayOfWeekMap[$dow]) ? $this->dayOfWeekMap[$dow] : null;
    }

    public function getMonthDaySelection()
    {
        $result = [
            self::MONTHLY_BY_DAY_OF_MONTH => Yii::t('CalendarModule.base', 'Monthly on day {dayOfMonth}', [
                'dayOfMonth' => $this->getStartDayOfMonth(),
            ]),
        ];

        // If the date is not in the fifth week of the month
        if (!$this->isFifthWeekOfMonth($this->getMonthlyPositionOfStart())) {
            $result[self::MONTHLY_BY_OCCURRENCE] = Yii::t('CalendarModule.base', 'Monthly on the {position} {dayOfWeek}', [
                'position' => $this->getMonthlyPositionOfStartFormatted(),
                'dayOfWeek' => $this->getStartDayOfWeekFormatted(),
            ]);
        }

        // If the day is in the last week of month we add the possibility for 'last' day of month
        if ($this->isLastWeekDayOfMonth()) {
            $result[self::MONTHLY_LAST_DAY_OF_MONTH] = Yii::t('CalendarModule.base', 'Monthly on the {position} {dayOfWeek}', [
                'position' =>  Yii::t('CalendarModule.base', 'last'),
                'dayOfWeek' => $this->getStartDayOfWeekFormatted(),
            ]);
        }

        return $result;
    }

    private function isLastWeekDayOfMonth()
    {
        $copy = clone $this->entry->getStartDateTime();
        return $this->entry->getStartDateTime()->format('m') !==  $copy->modify('+1 week')->format('m');
    }

    public function getIntervalTypesSelection()
    {
        return [
            static::FREQUENCY_NEVER => Yii::t('CalendarModule.base', 'Never'),
            Frequency::DAILY => Yii::t('CalendarModule.base', 'Day'),
            Frequency::WEEKLY => Yii::t('CalendarModule.base', 'Week'),
            Frequency::MONTHLY => Yii::t('CalendarModule.base', 'Month'),
            Frequency::YEARLY => Yii::t('CalendarModule.base', 'Year'),
        ];

    }

    public function getIntervalTypesSelectionData()
    {
        return [
            Frequency::DAILY => [
                'data-singular' => Yii::t('CalendarModule.base', 'Day'),
                'data-plural' => Yii::t('CalendarModule.base', 'Days'),
            ],
            Frequency::WEEKLY => [
                'data-singular' => Yii::t('CalendarModule.base', 'Week'),
                'data-plural' => Yii::t('CalendarModule.base', 'Weeks'),
            ],
            Frequency::MONTHLY => [
                'data-singular' => Yii::t('CalendarModule.base', 'Month'),
                'data-plural' => Yii::t('CalendarModule.base', 'Months'),
            ],
            Frequency::YEARLY => [
                'data-singular' => Yii::t('CalendarModule.base', 'Year'),
                'data-plural' => Yii::t('CalendarModule.base', 'Years'),
            ],
        ];
    }

    /**
     * @return int
     */
    private function getStartDayOfWeek()
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

    private function getMonthlyPositionOfStart()
    {
        $dayNum = strtolower($this->getStartDayOfMonth());
        return (int) floor(($dayNum - 1) / 7) + 1;
    }

    /**
     * @return string
     */
    private function getMonthlyPositionOfStartFormatted()
    {
        switch ($this->getMonthlyPositionOfStart()) {
            case 2:
                return Yii::t('CalendarModule.base', 'second');
            case 3:
                return Yii::t('CalendarModule.base', 'third');
            case 4:
                return Yii::t('CalendarModule.base', 'forth');
            case 5:
                return Yii::t('CalendarModule.base', 'last');
            default:
                return Yii::t('CalendarModule.base', 'first');
        }
    }

    public function getEndTypeSelection()
    {
        return [
            static::ENDS_NEVER => Yii::t('CalendarModule.base', 'Never'),
            static::ENDS_ON_DATE => Yii::t('CalendarModule.base', 'On date'),
            static::ENDS_AFTER_OCCURRENCES => Yii::t('CalendarModule.base', 'After (occurrences)'),
        ];
    }

    private function getStartDayOfWeekFormatted()
    {
        return CalendarUtils::getDayOfWeek($this->getStartDayOfWeek());
    }

    private function getStartDayOfMonth()
    {
        return $this->entry->getStartDateTime()->format('j');
    }
}
