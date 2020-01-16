<?php


namespace humhub\modules\calendar\models\recurrence;

use Exception;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use Sabre\VObject\Component;
use Sabre\VObject\Recur\EventIterator;
use Sabre\VObject\Recur\MaxInstancesExceededException;
use Sabre\VObject\Recur\NoInstancesException;
use Yii;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use yii\base\Model;
use DateTime;
use DateTimeZone;
use humhub\modules\calendar\interfaces\VCalendar;
use Sabre\VObject\Component\VEvent;


class CalendarRecurrenceExpand extends Model
{
    /**
     * @var RecurrentEventIF
     */
    public $event;

    /**
     * @var bool if true auto saves expanded events
     */
    public $saveInstnace = false;

    /**
     * @var \DateTimeZone
     */
    public $targetTimezone;

    /**
     * @var \DateTimeZone
     */
    public $eventTimeZone;

    public function init()
    {
        parent::init();

        if ($this->event->isAllDay()) {
            $this->targetTimezone = new DateTimeZone('UTC');
        } else if (!$this->targetTimezone) {
            $this->targetTimezone = CalendarUtils::getUserTimeZone();
        } else if (is_string($this->targetTimezone)) {
            $this->targetTimezone = new DateTimeZone($this->targetTimezone);
        }

        if ($this->event) {
            $this->eventTimeZone = new DateTimeZone($this->event->getTimezone());
        }
    }

    /**
     * Expands all recurrences between $start and $end
     * @param RecurrentEventIF $event
     * @param DateTime $start
     * @param DateTime $end
     * @param array $endResult
     * @param bool $save weather or not to automatically save the instances
     * @return RecurrentEventIF[]
     */
    public static function expand(RecurrentEventIF $event, DateTime $start, DateTime $end, array &$endResult = [], $save = false)
    {
        if (!RecurrenceHelper::isRecurrent($event)) {
            return [];
        }

        $event = static::assureRootEvent($event);
        $instance = new static(['event' => $event, 'saveInstnace' => $save]);
        return $instance->expandEvent($start, $end, $endResult);
    }

    /**
     * Expands a single recurrence with by recurrence id
     *
     * @param RecurrentEventIF $event
     * @param $recurrenceId
     * @param bool $save
     * @return RecurrentEventIF|null
     * @throws Exception
     * @throws \Throwable
     */
    public static function expandSingle(RecurrentEventIF $event, $recurrenceId, $save = true)
    {
        if (!RecurrenceHelper::isRecurrent($event)) {
            return null;
        }

        $event = static::assureRootEvent($event);
        $recurrence = $event->getRecurrenceQuery()->getRecurrenceInstance($recurrenceId);

        if ($recurrence) {
            return $recurrence;
        }

        $tz = new \DateTimeZone($event->getTimezone());
        $start = (new DateTime($recurrenceId, $tz))->modify("-1 minute");
        $end = (new DateTime($recurrenceId, $tz))->modify("+1 minute");

        $instance = new static(['event' => $event, 'saveInstnace' => $save]);
        $result = $instance->expandEvent($start, $end);

        foreach ($result as $recurrence) {
            if ($recurrence->getRecurrenceId() === CalendarUtils::cleanRecurrentId(new DateTime($recurrenceId, $tz))) {
                return $recurrence;
            }
        }

        return null;
    }

    /**
     * @param RecurrentEventIF $event
     * @param null $start
     * @param int $count
     * @param bool $save
     * @return RecurrentEventIF[]
     * @throws Exception
     */
    public static function expandUpcoming(RecurrentEventIF $event, $count = 1, $start = null , $save = true)
    {
        $from = new DateTime();
        $startIndex = 0;

        if($start instanceof \DateTimeInterface) {
            $from = $start;
            $startIndex = 0;
        } else if(is_bool($start)) {
            $save = $start;
            $startIndex = 0;
        } else if(is_int($start)) {
            $startIndex = $start;
        }

        if (!RecurrenceHelper::isRecurrent($event)) {
            return [];
        }

        $event = static::assureRootEvent($event);

        $vCalendar = (new VCalendar())->add($event);
        $eventTimeZone = CalendarUtils::getDateTimeZone($event->getTimezone());

        try {
            $it = new EventIterator([$vCalendar->getInstance()->VEVENT], null, $eventTimeZone);
        } catch (NoInstancesException $e) {
            return [];
        }

        $it->fastForward($from);

        $result = [];

        // Add startIndex to count
       $count += $startIndex;

        try {
            for ($i = 0; $i < $count && $it->valid(); $i++) {
                if($i >= $startIndex) {
                    $vEvent = static::stripTimezones($it->getEventObject(), $eventTimeZone);
                    $recurrenceId = RecurrenceHelper::getRecurrenceIdFromVEvent($vEvent, $event->getTimezone());
                    $existingModel = $event->getRecurrenceQuery()->getRecurrenceInstance($recurrenceId);
                    $existingModel = $existingModel ?: static::createRecurrenceInstanceModel($event, $vEvent, $save);
                    $result[] = $existingModel;
                }
                $it->next();
            }
        } catch (MaxInstancesExceededException $me) {
            Yii::warning($me);
        }

        return $result;
    }

    private static function assureRootEvent(RecurrentEventIF $event)
    {
        return RecurrenceHelper::isRecurrentRoot($event) ?  $event : $event->getRecurrenceQuery()->getRecurrenceRoot();
    }

    private static function stripTimezones(Component $component, $timeZone)
    {
        foreach ($component->children() as $componentChild) {
            if ($componentChild instanceof DateTime && $componentChild->hasTime()) {
                $dt = $componentChild->getDateTimes($timeZone);
                // We only need to update the first timezone, because
                // setDateTimes will match all other timezones to the
                // first.
                $dt[0] = $dt[0]->setTimeZone(new DateTimeZone('UTC'));
                $componentChild->setDateTimes($dt);
            } elseif
            ($componentChild instanceof Component) {
                static::stripTimezones($componentChild);
            }
        }

        return $component;
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @param array $endResult
     * @return RecurrentEventIF[]
     * @throws \Throwable
     */
    public function expandEvent(DateTime $start, DateTime $end, array &$endResult = [])
    {
        if (!RecurrenceHelper::isRecurrent($this->event)) {
            return [$this->event];
        }

        if (!$end) {
            $end = (new DateTime('now', $this->targetTimezone))->add(new \DateInterval('P2Y'));
        }

        $existingModels = $this->event->getRecurrenceQuery()->getExistingRecurrences($start, $end);
        $recurrencesUTC = $this->calculateRecurrenceInstances($start, $end);
        $this->syncRecurrences($existingModels, $recurrencesUTC, $endResult);

        return $endResult;
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return array
     * @throws Exception
     */
    private function calculateRecurrenceInstances(DateTime $start, DateTime $end)
    {
        // Note: VObject supports the EXDATE property for exclusions, but not yet the RDATE and EXRULE properties
        // Note: VCalendar expand will translate all dates with time to UTC
        $vCalendar = (new VCalendar())->add($this->event);
        $expandedVCalendar = $vCalendar->getInstance()->expand($start, $end, $this->event->isAllDay() ? null :$this->eventTimeZone);
        return $expandedVCalendar->select('VEVENT');
    }

    /**
     * @param RecurrentEventIF[] $existingModels
     * @param VEvent[] $recurrencesUTC
     * @param $endResult
     */
    private function syncRecurrences(array $existingModels, array $recurrencesUTC, &$endResult)
    {
        foreach ($recurrencesUTC as $vEventUTC) {
            try {
                /* @var $model RecurrentEventIF */
                /* @var $vEventStart DateTime */
                /* @var $vEventEnd DateTime */
                $model = null;
                $vEventStart = clone $vEventUTC->DTSTART->getDateTime();
                $vEventEnd = clone $vEventUTC->DTEND->getDateTime();

                if ($vEventStart == $vEventEnd && $this->event->isAllDay()) {
                    $vEventEnd = CalendarUtils::getDateTime($vEventEnd)->modify('+1 day');
                }

                // Check if recurrence model exists
                if (!$model) {
                    $model = $this->findRecurrenceModel($existingModels, $vEventUTC);
                }

                if (!$model) {
                    $model = static::createRecurrenceInstanceModel($this->event, $vEventUTC, $this->saveInstnace);
                }

                $endResult[] = $model;
            } catch (Exception $e) {
                Yii::error($e);
            }
        }
    }

    /**
     * @param RecurrentEventIF $root
     * @param VEvent $vEventUTC
     * @param bool $save
     * @return RecurrentEventIF
     * @throws Exception
     */
    private static function createRecurrenceInstanceModel(RecurrentEventIf $root, VEvent $vEventUTC, $save = false)
    {
        // Note VEvent uses datetime immutables
        $dtStart = CalendarUtils::getDateTime($vEventUTC->DTSTART->getDateTime());
        $dtEnd = CalendarUtils::getDateTime($vEventUTC->DTEND->getDateTime());

        if($root->isAllDay()) {
            CalendarUtils::ensureAllDay($dtStart, $dtEnd);
        } else {
            $dtStart->setTimezone(CalendarUtils::getSystemTimeZone());
            $dtEnd->setTimezone(CalendarUtils::getSystemTimeZone());
        }

        $model = $root->createRecurrence(
            CalendarUtils::toDBDateFormat($dtStart),
            CalendarUtils::toDBDateFormat($dtEnd)
        );

        $model->syncEventData($root, null);

        RecurrenceHelper::syncRecurrentEventData($root, $model,
            RecurrenceHelper::getRecurrenceIdFromVEvent($vEventUTC, $root->getTimezone())
        );

        if ($save) {
            if (!$model->saveEvent()) {
                throw new Exception('Could not safe recurrent event');
            }
        }

        return $model;
    }

    /**
     * @param RecurrentEventIF[] $existingModels
     * @param VEvent $vEvent
     * @return mixed|null
     */
    private function findRecurrenceModel(array $existingModels, VEvent $vEvent)
    {
        foreach ($existingModels as $existingModel) {
            if ($existingModel->getRecurrenceId() === RecurrenceHelper::getRecurrenceIdFromVEvent($vEvent, $this->event->getTimezone())) {
                return $existingModel;
            }
        }

        return null;
    }
}