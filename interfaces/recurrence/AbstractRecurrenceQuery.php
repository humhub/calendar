<?php


namespace humhub\modules\calendar\interfaces\recurrence;

use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\event\AbstractCalendarQuery;
use humhub\modules\calendar\models\recurrence\CalendarRecurrenceExpand;
use yii\db\ActiveQuery;

class AbstractRecurrenceQuery extends AbstractCalendarQuery
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

    public function setupFilters()
    {
        if ($this->isRecurrenceSupported()) {
            // Do not include existing recurrence instances
            $this->_query->andWhere($this->parentEventIdField . ' IS NULL');
        }

        return parent::setupFilters();
    }

    public function isRecurrenceSupported()
    {
        return $this->expand && is_subclass_of(static::$recordClass, RecurrentEventIF::class);
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
            $query = static::createQuery()->where([$this->parentEventIdField => $this->event->getRecurrenceRootId()])
                ->andWhere(['>', $this->startField, $start_datetime]);
        }

        return $query->orderBy($this->startField)->all();
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

    protected function getRruleRootQuery()
    {
        if($this->isRecurrenceSupported()) {
            return ['and',
                $this->rruleField . ' IS NOT NULL',
                $this->parentEventIdField . ' IS NULL'
            ];
        }

        return '';
    }

    /*public function getRecurrenceExceptions(DateTime $start = null, DateTime $end = null)
    {
        if($this->event instanceof CalendarEventSequenceIF) {
            return $this->findRecurrenceInstances($start, $end)->andWhere(['>', $this->sequenceField, 0])->all();
        }

        return [];
    }*/

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
            $entry->getEventQuery()->expandRoot($this->_from, $this->_to, $this->autoSaveRecurrentInstances, $expandResult);
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
    public function expandRoot($from = null, $to = null, $saveInstances = false, &$expandResult = [])
    {
        if(!RecurrenceHelper::isRecurrent($this->event)) {
            return $expandResult;
        }

        $event = $this->event;

        if (!RecurrenceHelper::isRecurrentRoot($this->event)) {
            $event = $this->event->getEventQuery()->getRecurrenceRoot();
        }

        $to = $to ?: (new \DateTime('now', CalendarUtils::getUserTimeZone()))->add(new \DateInterval('P1Y'));
        $from = $from ?: (new \DateTime('now', CalendarUtils::getUserTimeZone()))->sub(new \DateInterval('P1Y'));
        return CalendarRecurrenceExpand::expand($event, $from, $to, $expandResult, $saveInstances);
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
}