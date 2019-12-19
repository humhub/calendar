<?php


namespace humhub\modules\calendar\interfaces\recurrence;

use humhub\libs\DbDateValidator;
use humhub\modules\calendar\helpers\CalendarUtils;
use DateTime;
use humhub\modules\calendar\helpers\RRuleHelper;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use Recurr\Frequency;
use Recurr\Rule;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\web\HttpException;

class RecurrenceFormModel extends Model
{
    const EDIT_MODE_CREATE = 0;
    const EDIT_MODE_THIS = 1;
    const EDIT_MODE_FOLLOWING = 2;
    const EDIT_MODE_ALL = 3;

    const FREQUENCY_NEVER = -1;

    const MONTHLY_BY_DAY_OF_MONTH = 1;
    const MONTHLY_BY_OCCURRENCE = 2;

    const ENDS_NEVER = 0;
    const ENDS_ON_DATE = 1;
    const ENDS_AFTER_OCCURRENCES = 2;

    /**
     * @var RecurrentCalendarEventIF
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
     * @var integer
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
                $endDate = $this->rrule->getUntil();
                $endDate->setTimeZone(new \DateTimeZone('UTC'));
                $this->setEndDate($endDate);
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
            ['endDate', DbDateValidator::class, 'timeZone' => new \DateTimeZone('UTC')],
            ['recurrenceEditMode', 'integer', 'min'  => static::EDIT_MODE_THIS, 'max' => static::EDIT_MODE_ALL],
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
        if($this->recurrenceEditMode === static::EDIT_MODE_ALL && !RecurrenceHelper::isRecurrentRoot($this->entry)) {
            // Currently the edit mode all is only valid if the root event itself is given due to load complexity...
            throw new HttpException(400, 'No root event given for edit mode all!');
        }

        if (!($this->entry instanceof RecurrentCalendarEventIF)) {
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
        if ($this->frequency == static::FREQUENCY_NEVER) {
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

    public function save(RecurrentCalendarEventIF $original = null)
    {
        if (!$this->validate()) {
            return false;
        }

        switch($this->recurrenceEditMode) {
            case static::EDIT_MODE_CREATE:
                return $this->saveCreate();
            case static::EDIT_MODE_THIS:
                // We only want to save this instance, so we ignore rrule changes
                return true;
            case static::EDIT_MODE_FOLLOWING:
                return $original ? $this->saveSplit($original) : false;
            case static::EDIT_MODE_ALL:
                return $original ? $this->saveAll($original) : false;
        }

        return true;
    }

    protected function saveSplit(RecurrentCalendarEventIF $original)
    {
        if(RecurrenceHelper::isRecurrentRoot($this->entry)) {
            return $this->saveAll($original);
        }

        try {
            // Update until of old root
            $root = $this->entry->getRecurrenceRoot();
            $isFirstInstanceEdit = RecurrenceHelper::getRecurrentId($root) === $this->entry->getRecurrenceId();

            // Generate new UID
            $this->entry->setUid(CalendarUtils::generateUUid());
            $this->entry->setRecurrenceId(null);

            // Save this instance as a new recurrence root
            $this->entry->setRecurrenceRootId(null);
            $this->saveCreate();

            $this->syncFollowingInstances($original);

            if($isFirstInstanceEdit) {
                // We are editing the first instance, so we do not need the old root anymore
                // TODO: what about attached files?
                $root->delete();
            } else {
                $root->setRrule(RRuleHelper::setUntil($root->getRrule(), $this->entry->getStartDateTime()->modify('-1 hour')));
                $root->saveRecurrenceInstance();
            }


        } catch (\Exception $e) {
            Yii::error($e);
            return false;
        }

        return true;
    }

    protected function saveAll(RecurrentCalendarEventIF $original)
    {
        $this->saveCreate();
        $this->syncFollowingInstances($original);
        return true;
    }

    protected function saveCreate()
    {
        try {
            if ($this->interval === static::FREQUENCY_NEVER) {
                $this->entry->setRrule(null);
            } else {
                $this->entry->setRrule($this->buildRRuleString());

                if ($this->entry instanceof ActiveRecord) {
                    return $this->entry->save();
                }
            }
        } catch (\Exception $e) {
            $this->addError('frequency', 'Could not save recurrent rule');
            Yii::error($e);
            return false;
        }

        return true;
    }

    protected function syncFollowingInstances(RecurrentCalendarEventIF $original)
    {
        $followingInstances = $original->getFollowingInstances();

        // Sync following events
        if (!empty($followingInstances)) {
            $lastInstance = end($followingInstances);

            /**
             * If editMode = all $original is the root node and we want to sync all existing recurrence instances
             * If editMode = following $original is an old recurrent instance which itself should be excluded from sync
             */
            $searchStartDate = $this->recurrenceEditMode == static::EDIT_MODE_ALL
                ? $original->getStartDateTime() : $original->getEndDateTime();

            // If not recurrent, we delete all following instances
            $remainingRecurrenceIds = ($this->interval === static::FREQUENCY_NEVER)
                ? []
                : RecurrenceHelper::getRecurrenceIds($this->entry, $searchStartDate, $lastInstance->getEndDateTime());

            foreach ($followingInstances as $followingInstance) {
                if (!in_array($followingInstance->getRecurrenceId(), $remainingRecurrenceIds)) {
                    $followingInstance->deleteRecurrenceInstance();
                } else {
                    $followingInstance->syncFromRecurrentRoot($this->entry);
                    $followingInstance->saveRecurrenceInstance();
                }
            }
        }
    }

    private function rruleChanged($ignoreUntil = false, $rruleToCompare = null)
    {
        if(!$rruleToCompare) {
            $rruleToCompare = $this->buildRRuleString();
        }

        return RRuleHelper::compare($this->entry->getRrule(), $rruleToCompare);
    }

    /**
     * @return bool
     * @throws \Recurr\Exception\InvalidRRule
     */
    private function handleRecurrentUpdate($dateChanged = false, $recurrenceChanged = false)
    {
        // TODO: put into recurrenceForm
        if(!RecurrenceHelper::isRecurrent($this->entry)) {
            return true;
        }

        switch ($this->recurrenceEditMode) {
            case static::EDIT_MODE_FOLLOWING:
                if($recurrenceChanged || $dateChanged) {
                    $root = $this->entry->getRoot();
                    $rule = new Rule($root->getRrule());
                    $rule->setUntil($this->entry->getStartDateTime()->modify('-1 hour'));

                    if($root instanceof ActiveRecord) {
                        $root->updateAttributes(['rrule' => $this->rrule->getString(Rule::TZ_FIXED)]);
                    } else {
                        $root->setRrule($this->rrule->getString(Rule::TZ_FIXED));
                    }
                }
                // if recurrence was changed || date was changed
                // get parent and set until = $this->entry->start (check google)
                // delete all instances after until
                // save this entry as new recurrence root
                // else
                // update all following instances
                break;
            case static::EDIT_MODE_ALL:
                // if recurrence was changed
                // update parent
                // delete all instances
                // else
                // update all following instances
                break;
            default:
                // TODO: mark as exception in case of recurrence
                // IGNORE RECURRENCE
                return $this->entry->save() ? [$this->entry] : false;
        }
    }

    public function recurrenceChanged()
    {
        return $this->entry->getRrule() !== $this->entry->getOldAttribute('rrule');
    }

    private function dateChanged()
    {
        return $this->entry->start_datetime !== $this->entry->getOldAttribute('start_datetime')
            || $this->entry->end_datetime !== $this->entry->getOldAttribute('end_datetime');
    }

    /**
     * @return string|null
     * @throws \Recurr\Exception\InvalidArgument
     * @throws \Recurr\Exception\InvalidRRule
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
                : new DateTime($this->endDate, new \DateTimeZone('UTC'));
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