<?php


namespace humhub\modules\calendar\interfaces\recurrence;

use humhub\modules\calendar\interfaces\event\EditableEventIF;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\calendar\models\reminder\CalendarReminderSent;
use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\helpers\RRuleHelper;
use humhub\modules\calendar\interfaces\event\AbstractCalendarQuery;
use humhub\modules\calendar\models\recurrence\CalendarRecurrenceExpand;

class AbstractRecurrenceQuery extends AbstractCalendarQuery implements RecurrenceQueryIF
{
    public $recurrenceIdField = 'recurrence_id';

    public $sequenceField = 'sequence';

    public $idField = 'id';

    /**
     * @var string database field for rrule (optional)
     */
    public $rruleField = 'rrule';

    /**
     * @var string
     */
    public $parentEventIdField = 'parent_event_id';

    /**
     * @var RecurrentEventIF
     */
    public $event;

    protected function setupFilters()
    {
        if ($this->isRecurrenceSupported()) {
            // Do not include existing recurrence instances
            $this->_query->andWhere($this->parentEventIdField . ' IS NULL');
        }

        return parent::setupFilters();
    }

    protected function isRecurrenceSupported()
    {
        return $this->expand && is_subclass_of(static::$recordClass, RecurrentEventIF::class);
    }

    /**
     * @param DateTime|null $start
     * @param DateTime|null $end
     * @return ActiveQuery
     * @throws \Throwable
     */
    public function getExistingRecurrences(DateTime $start = null, DateTime $end = null)
    {
        return $this->findRecurrenceInstances($start,$end)->all();
    }

    /**
     * @return ActiveQuery
     */
    protected function findRecurrenceInstances(DateTime $start = null, DateTime $end = null)
    {
        $query = static::createQuery()->andWhere([$this->parentEventIdField => $this->event->getId()]);
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
     * @param $recurrence_id
     * @param bool $save
     * @return RecurrentEventIF|null
     * @throws \Throwable
     */
    public function expandSingle($recurrence_id, $save = true)
    {
        return CalendarRecurrenceExpand::expandSingle($this->event, $recurrence_id, true);
    }

    protected function isRecurrenceRootCondition()
    {
        if($this->isRecurrenceSupported()) {
            return ['and',
                $this->rruleField . ' IS NOT NULL',
                $this->parentEventIdField . ' IS NULL'
            ];
        }

        return '';
    }


    /**
     * @param DateTime|null $start
     * @param DateTime|null $end
     * @return array|CalendarReminder[]|CalendarReminderSent[]|ActiveRecord[]
     */
     public function getRecurrenceExceptions(DateTime $start = null, DateTime $end = null)
     {
            if($this->event instanceof EditableEventIF) {
                return $this->findRecurrenceInstances($start, $end)->andWhere(['>', $this->sequenceField, 0])->all();
            }

            return [];
     }

    /**
     * This function can be used for subclasses to expand e.g. recurrent events by adding all expanded events to the
     * endResult array.
     *
     * > Note: currently there is no defaul timplementation/helper for recurring events
     * > Note: the $endResult array does not contain the given $entry.
     *
     * @param $entry
     * @param $expandResult
     * @throws \Throwable
     */
    protected function expand($entry, &$expandResult)
    {
        if (!$this->isRecurrenceSupported()) {
            parent::expand($entry, $expandResult);
            return;
        }

        /* @var $entry RecurrentEventIF */

        // Make sure we only expand recurrence roots
        if (RecurrenceHelper::isRecurrentRoot($entry)) {
            $entry->getRecurrenceQuery()->expandEvent($this->_from, $this->_to, $this->autoSaveRecurrentInstances, $expandResult);
        } else {
            $expandResult[] = $entry;
        }
    }

    /**
     * @param bool $saveInstances
     * @param null $from
     * @param null $to
     * @param array $expandResult
     * @return array
     * @throws \Throwable
     */
    public function expandEvent($from = null, $to = null, $save = false, &$expandResult = [])
    {
        if(!RecurrenceHelper::isRecurrent($this->event)) {
            return $expandResult;
        }

        $to = $to ?: (new \DateTime('now', CalendarUtils::getUserTimeZone()))->add(new \DateInterval('P1Y'));
        $from = $from ?: (new \DateTime('now', CalendarUtils::getUserTimeZone()))->sub(new \DateInterval('P1Y'));
        return CalendarRecurrenceExpand::expand($this->event, $from, $to, $expandResult, $save);
    }

    /**
     * @param null $from
     * @param int $count
     * @param bool $save
     * @return RecurrentEventIF[]
     * @throws \Exception
     */
    public function expandUpcoming($count = 1, $from = null, $save = true)
    {
        return CalendarRecurrenceExpand::expandUpcoming($this->event, $count, $from, $save);
    }

    /**
     * @return RecurrentEventIF
     * @throws \Throwable
     */
    public function getRecurrenceRoot()
    {
        return static::createQuery()->where([$this->idField => $this->event->getRecurrenceRootId()])->one();
    }

    /**
     * @param $recurrent_id
     * @return RecurrentEventIF|null
     * @throws \Throwable
     */
    public function getRecurrenceInstance($recurrent_id)
    {
        return $this->findRecurrenceInstances()->andWhere([$this->recurrenceIdField => $recurrent_id])->one();
    }

    private static $deletedRoot = [];

    /**
     * @param RecurrentEventIF $event
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function onDelete()
    {
        if(RecurrenceHelper::isRecurrentRoot($this->event)) {
            self::$deletedRoot[] = $this->event->getUid();
            foreach($this->getFollowingInstances() as $recurrence) {
                $recurrence->delete();
            }
        } elseif(RecurrenceHelper::isRecurrentInstance($this->event)) {
            $root = $this->getRecurrenceRoot();
            if($root && !in_array($root->getUid(), self::$deletedRoot, true)) {
                $root->setExdate(RecurrenceHelper::addExdates($root, $this->event));
                $root->saveEvent();
            }
        }

        self::$deletedRoot = [];
    }

    /**
     * @param RecurrentEventIF $original
     * @param RecurrentEventIF $event
     * @return bool
     * @throws \Throwable
     */
    public function saveThisAndFollowing(RecurrentEventIF $original)
    {
        if(RecurrenceHelper::isRecurrentRoot($this->event)) {
            return $this->saveAll($original);
        }

        try {
            // Update until of old root
            $root = $this->getRecurrenceRoot();

            $isFirstInstanceEdit = $root->getStartDateTime() == $original->getStartDateTime();

            // Generate new UID
            $this->event->setUid(CalendarUtils::generateEventUid($this->event));
            $this->event->setRecurrenceId(null);

            // Save this instance as a new recurrence root
            $this->event->setRecurrenceRootId(null);

            $this->syncFollowingInstances($original, true);

            $this->event->saveEvent();

            if($isFirstInstanceEdit) {
                // We are editing the first instance, so we do not need the old root anymore
                // TODO: what about attached files?
                $root->delete();
            } else {
                $splitDate = $original->getStartDateTime()->setTimezone(CalendarUtils::getStartTimeZone($this->event))->modify('-1 hour');
                $root->setRrule(RRuleHelper::setUntil($root->getRrule(), $splitDate));
                $root->saveEvent();
            }

        } catch (\Exception $e) {
            Yii::error($e);
            return false;
        }

        return true;
    }

    /**
     * @param RecurrentEventIF $original
     * @return bool
     * @throws \Throwable
     */
    public function saveAll(RecurrentEventIF $original)
    {
        if(!$this->event->saveEvent()) {
            return false;
        }

        $this->syncFollowingInstances($original, false);
        return true;
    }

    /**
     * @param RecurrentEventIF $original
     * @param $isSplit
     * @throws \Throwable
     */
    protected function syncFollowingInstances(RecurrentEventIF $original, $isSplit)
    {
        $followingInstances = $original->getRecurrenceQuery()->getFollowingInstances();

        // Sync following events
        if (!empty($followingInstances)) {
            $lastInstance = end($followingInstances);

            /**
             * If editMode = all -> $original is the root node and we want to sync all existing recurrence instances
             * If editMode = following -> $original is an old recurrent instance which itself should be excluded from sync
             */
            $searchStartDate = $isSplit ?  $original->getStartDateTime() : $original->getEndDateTime();

            // If not recurrent, we delete all following instances
            $remainingRecurrenceIds = !RecurrenceHelper::isRecurrent($this->event)
                ? []
                : RecurrenceHelper::getRecurrenceIds($this->event, $searchStartDate, $lastInstance->getEndDateTime());

            foreach ($followingInstances as $followingInstance) {
                if($followingInstance->getId() === $original->getId()) {
                    continue; // Skip self
                }

                if (!in_array($followingInstance->getRecurrenceId(), $remainingRecurrenceIds)) {
                    $followingInstance->delete();
                } else {
                    RecurrenceHelper::syncRecurrentEventData($this->event, $followingInstance);
                    $followingInstance->syncEventData($this->event, $original);
                    $followingInstance->saveEvent();
                }
            }

            if(!$isSplit) {
                $this->event->setExdate(null);
            } else {
                // This event is new recurrent root
                // TODO: filter out non valid exdates
                $this->event->setExdate($original->getExdate());
            }
        }
    }

    /**
     * Returns existing recurrence instances following this $this->event. Note this function should not create any instance
     * or return recurrent instances which are not persisted.
     *
     * @return RecurrentEventIF[]
     * @throws \Throwable
     */
    public function getFollowingInstances()
    {
        if(RecurrenceHelper::isRecurrentRoot($this->event)) {
            $query = static::createQuery()->where([$this->parentEventIdField => $this->event->getId()]);
        } else {
            // Make sure we use the original date, the start_date may have been overwritten
            $start_datetime = RecurrenceHelper::recurrenceIdToDate($this->event->getRecurrenceId());
            $query = static::createQuery()
                ->where([$this->parentEventIdField => $this->event->getRecurrenceRootId()])
                ->andWhere(['>', $this->startField, $start_datetime]);
        }

        return $query->orderBy($this->startField)->all();
    }
}