<?php


namespace humhub\modules\calendar\interfaces\recurrence;

use humhub\libs\DbDateValidator;
use humhub\modules\calendar\helpers\CalendarUtils;
use DateTime;
use Recurr\Frequency;
use Recurr\Rule;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;

class RecurrenceFormModel extends Model
{
    const FREQUENCY_NEVER = -1;

    const MONTHLY_BY_DAY_OF_MONTH = 1;
    const MONTHLY_BY_OCCURRENCE = 2;

    const ENDS_NEVER = 0;
    const ENDS_ON_DATE = 1;
    const ENDS_AFTER_OCCURRENCES = 2;

    /**
     * @var RecurrentCalendarEntry
     */
    public $entry;

    public $interval = 1;

    public $frequency = self::FREQUENCY_NEVER;

    public $weekDays;

    public $monthDaySelection = RecurrenceFormModel::MONTHLY_BY_DAY_OF_MONTH;

    public $end = self::ENDS_NEVER;

    public $endDate;

    public $endOccurrences = 10;

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
     * @throws \Recurr\Exception\InvalidRRule
     */
    public function init()
    {
        parent::init();
        $this->initRrule($this->entry->getRrule());
    }

    /**
     * @param null $rruleStr
     * @return $this
     * @throws \Recurr\Exception\InvalidRRule
     */
    private function initRrule($rruleStr = null)
    {
        $this->rrule = new Rule($rruleStr);

        if ($rruleStr) {
            $this->interval = $this->rrule->getInterval();
            $this->frequency = $this->rrule->getFreq();
            $this->weekDays = $this->rrule->getByDay();

            if ($this->frequency == Frequency::MONTHLY && $this->rrule->getBySetPosition() !== null) {
                $this->monthDaySelection = static::MONTHLY_BY_OCCURRENCE;
            } else if ($this->frequency) {
                $this->monthDaySelection = static::MONTHLY_BY_DAY_OF_MONTH;
            }

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
                $this->setEndDate($this->rrule->getUntil());
            } else if ($this->rrule->getCount()) {
                $this->end = static::ENDS_AFTER_OCCURRENCES;
                $this->endOccurrences = $this->rrule->getCount();
            }
        }

        if (empty($this->weekDays)) {
            $this->weekDays = [$this->getStartDayOfWeek()];
        }

        if (empty($this->endDate)) {
            $endDate = clone $this->entry->getStartDateTime();
            $this->setEndDate($endDate->modify('+1 years')->setTime(0, 0));
        }

        return $this;
    }

    public function setEndDate($endDate)
    {
        if (is_string($endDate)) {
            $this->endDate = $endDate;
        } else if ($endDate instanceof \DateTimeInterface) {
            $this->endDate = $endDate->format(CalendarUtils::DB_DATE_FORMAT);
        }
    }

    public function rules()
    {
        return [
            ['interval', 'integer', 'min' => 1],
            ['weekDays', 'safe'], //TODO: better validation
            ['frequency', 'integer', 'min' => static::FREQUENCY_NEVER, 'max' => Frequency::DAILY],
            ['monthDaySelection', 'integer', 'min' => static::MONTHLY_BY_DAY_OF_MONTH, 'max' => static::MONTHLY_BY_OCCURRENCE],
            ['frequency', 'validateFrequency'],
            ['frequency', 'validateModel'],
            ['end', 'integer', 'min' => static::ENDS_NEVER, 'max' => static::ENDS_AFTER_OCCURRENCES],
            ['endOccurrences', 'integer'],
            ['endDate', DbDateValidator::class]
        ];
    }

    public function attributeLabels()
    {
        return [
            'frequency' => Yii::t('CalendarModule.recurrence', 'Repeat every'),
            'weekDays' => Yii::t('CalendarModule.recurrence', 'on weekdays'),
            'end' => Yii::t('CalendarModule.recurrence', 'End'),
        ];
    }

    public function validateModel($attribute, $params)
    {
        if (!($this->entry instanceof RecurrentCalendarEntry)) {
            $this->addError('frequency', Yii::t('CalendarModule.recurrence', 'This event does not support recurrent events'));
        }
    }

    /**
     * @param $attribute
     * @param $params
     * @return |null
     * @throws \Recurr\Exception\InvalidRRule
     */
    public function validateFrequency($attribute, $params)
    {
        if ($this->frequency === static::FREQUENCY_NEVER) {
            return null;
        }

        $this->initRrule();

        try {
            $this->setRuleInterval();
        } catch (\Exception $e) {
            $this->addError('interval', Yii::t('CalendarModule.recurrence', 'Invalid interval given'));
        }

        try {
            $this->setRuleFrequency();
        } catch (\Exception $e) {
            $this->addError('frequency', Yii::t('CalendarModule.recurrence', 'Invalid frequency given'));
        }

        try {
            $this->setRuleDay();

            if ($this->frequency == Frequency::WEEKLY && empty($this->weekDays)) {
                $this->weekDays = [$this->getStartDayOfWeek()];
            }

        } catch (\Exception $e) {
            if ($this->interval == Frequency::MONTHLY) {
                $this->addError('monthDaySelection', Yii::t('CalendarModule.recurrence', 'Invalid day of month given'));
            } else if ($this->interval == Frequency::WEEKLY) {
                $this->addError('weekDays', Yii::t('CalendarModule.recurrence', 'Invalid week day selection'));
            }
        }
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        if ($this->interval === static::FREQUENCY_NEVER) {
            $this->entry->setRrule(null);

            // TODO: delete all recurrences
            return;
        }

        try {
            $this->entry->setRrule($this->buildRRuleString());
        } catch (\Exception $e) {
            $this->addError('frequency', 'Could not save recurrent rule');
            Yii::error($e);
            return false;
        }

        if ($this->entry instanceof ActiveRecord) {
            return $this->entry->save();
        }

        return true;
    }

    /**
     * @return string|null
     * @throws \Recurr\Exception\InvalidArgument
     * @throws \Recurr\Exception\InvalidRRule
     */
    public function buildRRuleString()
    {
        if ($this->frequency === static::FREQUENCY_NEVER) {
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
                : new DateTime($this->endDate);
            $this->rrule->setUntil($until);
        } else if ($this->end == static::ENDS_AFTER_OCCURRENCES) {
            $this->rrule->setCount($this->endOccurrences);
        }

        return $this;
    }

    /**
     * @param Rule $rrule
     * @return RecurrenceFormModel
     * @throws \Recurr\Exception\InvalidArgument
     */
    private function setRuleInterval()
    {
        $this->rrule->setInterval((int)$this->interval);
        return $this;
    }

    /**
     * @param Rule $rrule
     * @return RecurrenceFormModel
     * @throws \Recurr\Exception\InvalidArgument
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
     * @throws \Recurr\Exception\InvalidRRule
     */
    private function setRuleDay()
    {
        if ($this->frequency == Frequency::WEEKLY) {
            $this->rrule->setByDay($this->getByDays());
        } else if ($this->frequency == Frequency::MONTHLY) {
            if ($this->monthDaySelection == static::MONTHLY_BY_OCCURRENCE) {
                $this->rrule->setByDay([$this->translateDayOfWeekToRrule($this->getStartDayOfWeek())]);
                $this->rrule->setBySetPosition([$this->getMonthlyPositionOfStart()]);
            } else if ($this->monthDaySelection == static::MONTHLY_BY_DAY_OF_MONTH) {
                $this->rrule->setByMonthDay([$this->getStartDayOfMonth()]);
            }
        }

        return $this;
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

    private function translateDayOfWeekToRrule($dow, $occurrence = null)
    {
        $result = isset($this->dayOfWeekMap[$dow]) ? $this->dayOfWeekMap[$dow] : null;
        return is_int($occurrence) ? $occurrence . $result : $result;
    }

    public function getMonthDaySelection()
    {
        return [
            self::MONTHLY_BY_DAY_OF_MONTH => Yii::t('CalendarModule.recurrence', 'Monthly on day {dayOfMonth}', [
                'dayOfMonth' => $this->getStartDayOfMonth()
            ]),
            self::MONTHLY_BY_OCCURRENCE => Yii::t('CalendarModule.recurrence', 'Monthly on the {position} {dayOfWeek}', [
                'position' => $this->getMonthlyPositionOfStartFormatted(),
                'dayOfWeek' => $this->getStartDayOfWeekFormatted()
            ]),
        ];
    }

    public function getIntervalTypesSelection()
    {
        return [
            static::FREQUENCY_NEVER => Yii::t('CalendarModule.recurrence', 'Never'),
            Frequency::DAILY => Yii::t('CalendarModule.recurrence', 'Day'),
            Frequency::WEEKLY => Yii::t('CalendarModule.recurrence', 'Week'),
            Frequency::MONTHLY => Yii::t('CalendarModule.recurrence', 'Month'),
            Frequency::YEARLY => Yii::t('CalendarModule.recurrence', 'Year'),
        ];

    }

    public function getIntervalTypesSelectionData()
    {
        return [
            Frequency::DAILY => [
                'data-singular' => Yii::t('CalendarModule.recurrence', 'Day'),
                'data-plural' => Yii::t('CalendarModule.recurrence', 'Days')
            ],
            Frequency::WEEKLY => [
                'data-singular' => Yii::t('CalendarModule.recurrence', 'Week'),
                'data-plural' => Yii::t('CalendarModule.recurrence', 'Weeks')
            ],
            Frequency::MONTHLY => [
                'data-singular' => Yii::t('CalendarModule.recurrence', 'Month'),
                'data-plural' => Yii::t('CalendarModule.recurrence', 'Months')
            ],
            Frequency::YEARLY => [
                'data-singular' => Yii::t('CalendarModule.recurrence', 'Year'),
                'data-plural' => Yii::t('CalendarModule.recurrence', 'Years')
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
        return floor(($dayNum - 1) / 7) + 1;
    }

    /**
     * @return string
     */
    private function getMonthlyPositionOfStartFormatted()
    {
        switch ($this->getMonthlyPositionOfStart()) {
            case 2:
                return Yii::t('CalendarModule.recurrence', 'second');
            case 3:
                return Yii::t('CalendarModule.recurrence', 'third');
            case 4:
                return Yii::t('CalendarModule.recurrence', 'forth');
            default:
                return Yii::t('CalendarModule.recurrence', 'first');
        }
    }

    public function getEndTypeSelection()
    {
        return [
            static::ENDS_NEVER => Yii::t('CalendarModule.recurrence', 'Never'),
            static::ENDS_ON_DATE => Yii::t('CalendarModule.recurrence', 'On date'),
            static::ENDS_AFTER_OCCURRENCES => Yii::t('CalendarModule.recurrence', 'After (occurrences)'),
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