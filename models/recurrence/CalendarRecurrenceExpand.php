<?php


namespace humhub\modules\calendar\models\recurrence;

use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\content\components\ActiveQueryContent;
use Yii;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use yii\base\Model;
use DateTime;
use DateTimeZone;
use humhub\modules\calendar\interfaces\VCalendar;
use Sabre\VObject\Component\VEvent;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;


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

    /**
     * @var string database field for start date
     */
    public $startField = 'start_datetime';

    /**
     * @var string database field for start date
     */
    public $parentIdField = 'parent_event_id';

    /**
     * @var string database field for recurrence id
     */
    public $recurrenceIdField = 'recurrence_id';

    /**
     * @var string database field for end date
     */
    public $endField = 'end_datetime';

    public function init()
    {
        parent::init();

        if($this->event->isAllDay()) {
            $this->targetTimezone = new DateTimeZone('UTC');
        } else if(!$this->targetTimezone) {
            $this->targetTimezone = CalendarUtils::getUserTimeZone();
        } else if(is_string($this->targetTimezone)) {
            $this->targetTimezone = new DateTimeZone($this->targetTimezone);
        }

        if($this->event) {
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
     * @throws \Exception
     */
    public static function expandSingle(RecurrentEventIF $event, $recurrenceId, $save = true)
    {
        $instance = new static(['event' => $event, 'saveInstnace' => $save]);
        $recurrence = $instance->getRecurrence($recurrenceId);

        if($recurrence) {
            return $recurrence;
        }

        $tz = new \DateTimeZone($event->getTimezone());
        $start = (new DateTime($recurrenceId,$tz))->modify("-1 minute");
        $end = (new DateTime($recurrenceId, $tz))->modify("+1 minute");

        $instance = new static(['event' => $event, 'saveInstnace' => $save]);
        $result = $instance->expandEvent($start, $end);

        foreach ($result as $recurrence) {
            if($recurrence->getRecurrenceId() === CalendarUtils::cleanRecurrentId(new DateTime($recurrenceId,$tz))) {
                return $recurrence;
            }
        }

        return null;
    }

    protected function getRecurrenceEntry($root, $id)
    {
        /* @var ActiveQueryContent $query */
        $query = call_user_func(get_class($root).'::find');

        /** @var ContentActiveRecord $instance */
        $tableName = call_user_func($this->entryClass.'::tableName');
        $entry = $query->contentContainer($this->contentContainer)->readable()->where([$tableName.'.id' => $id])->one();

        if(!$entry) {
            throw new NotFoundHttpException();
        }

        return $entry;
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
        if(empty($this->event->getRrule())) {
            return [$this->event];
        }

        //$rootRecurrenceId = RecurrenceHelper::cleanRecurrentId($this->event->getStartDateTime());

       /* // Make sure to set or update the recurrence id of root
        if($this->event->getRecurrenceId() !== $rootRecurrenceId) {
            $this->event->setRecurrenceId($rootRecurrenceId);
            if($this->event instanceof ActiveRecord) {
                $this->event->save();
            }
        }*/

        if(!$end) {
            $end = (new DateTime('now', $this->targetTimezone))->add(new \DateInterval('P2Y'));
        }

        $existingModels = $this->findExistingRecurrences($start, $end)->all();
        $recurrences = $this->calculateRecurrenceInstances($start, $end);
        $this->syncRecurrences($existingModels, $recurrences, $endResult);

        return $endResult;
    }

    public function findExistingRecurrences(DateTime $start = null, DateTime $end = null)
    {
        /** @var ActiveQuery $query */
        $query = call_user_func(get_class($this->event) .'::find');
        $query->andWhere([$this->parentIdField => $this->event->getId()]);

        if($start && $end) {
        $query->andFilterWhere(
            ['or',
                ['and',
                    ['>=', $this->startField, $start->format('Y-m-d H:i:s')],
                    ['<=', $this->startField, $end->format('Y-m-d H:i:s')]
                ],
                ['and',
                    ['>=', $this->endField, $start->format('Y-m-d H:i:s')],
                    ['<=', $this->endField, $end->format('Y-m-d H:i:s')]
                ]
            ]);
        }
        return $query;
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return array
     * @throws \Exception
     */
    private function calculateRecurrenceInstances(DateTime $start, DateTime $end)
    {
        // Note: VObject supports the EXDATE property for exclusions, but not yet the RDATE and EXRULE properties
        // Note: VCalendar expand will translate all dates with time to UTC
        $vCalendar = (new VCalendar())->add($this->event);
        $expandedVCalendar = $vCalendar->getInstance()->expand($start, $end, $this->eventTimeZone);
        return $expandedVCalendar->select('VEVENT');
    }

    /**
     * @param RecurrentEventIF[] $existingModels
     * @param VEvent[] $recurrences
     * @param $endResult
     */
    private function syncRecurrences(array $existingModels, array $recurrences, &$endResult)
    {
        foreach($recurrences as $vEvent) {
            try {
                $model = null;
                $vEventStart = clone $vEvent->DTSTART->getDateTime();
                $vEventEnd = clone $vEvent->DTEND->getDateTime();

                if($vEventStart == $vEventEnd && $this->event->isAllDay()) {
                    $vEventEnd =  $vEventEnd->modify('+1 day')->modify('-1 second');
                }

                // Check if this recurrence is the first one
                /*if ($this->event->getRecurrenceId() === RecurrenceHelper::getRecurrenceIdFromVEvent($vEvent, $this->event->getTimezone())) {
                    $model = $this->event;
                }*/

                if (!$model) {
                    $model = $this->findRecurrenceModel($existingModels, $vEvent);
                }

                if (!$model) {
                    $model = $this->event->createRecurrence(
                        CalendarUtils::toDBDateFormat($vEventStart, true), CalendarUtils::toDBDateFormat($vEventEnd, true),
                        RecurrenceHelper::getRecurrenceIdFromVEvent($vEvent, $this->event->getTimezone()));

                    if($this->saveInstnace && $model instanceof ActiveRecord) {
                        if(!$model->save()) {
                            throw new \Exception('Could not safe recurrent event');
                        }
                    }

                }

                $endResult[] = $model;
            } catch (\Exception $e) {
                Yii::error($e);
            }
        }
    }

    /**
     * @param RecurrentEventIF[] $existingModels
     * @param VEvent $vEvent
     * @return mixed|null
     */
    private function findRecurrenceModel(array $existingModels, VEvent $vEvent)
    {
        foreach ($existingModels as $existingModel) {
            if($existingModel->getRecurrenceId() === RecurrenceHelper::getRecurrenceIdFromVEvent($vEvent, $this->event->getTimezone())) {
                return $existingModel;
            }
        }

        return null;
    }

    public function getRecurrence($recurrenceId) {
        /*if ($this->event->getRecurrenceId() === $recurrenceId) {
            return $this->event;
        }*/
        return $this->findExistingRecurrences()->andWhere(['recurrence_id' => CalendarUtils::cleanRecurrentId($recurrenceId)])->one();
    }

}