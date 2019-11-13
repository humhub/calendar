<?php


namespace humhub\modules\calendar\interfaces;


use humhub\modules\calendar\interfaces\recurrence\RecurrentCalendarEvent;
use humhub\modules\content\components\ContentActiveRecord;

/**
 * Class AbstractRecurrentEvent
 * @package humhub\modules\calendar\interfaces
 * @property integer $id
 * @property string $rrule
 * @property string recurrence_id
 * @property string exdate
 * @property integer parent_event_id
 */
abstract class AbstractRecurrentEvent extends ContentActiveRecord implements RecurrentCalendarEvent
{
    public function setRrule($rrule)
    {
        $this->rrule = $rrule;
    }

    public function getRecurrenceId()
    {
        return $this->recurrence_id;
    }

    public function setRecurrenceId($recurrenceId)
    {
        $this->recurrence_id = $recurrenceId;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRrule() {
        return $this->rrule;
    }

    public function getExdate()
    {
        return $this->hasProperty('exdate') ? $this->exdate : null;
    }

    public function getParentId()
    {
        return $this->parent_event_id;
    }

    public function getRecurrenceInstance($recurrent_id)
    {
        return static::findOne(['parent_event_id' => $this->getId(), 'recurrence_id' => $recurrent_id]);
    }

    public function isRecurringInstance()
    {
        return $this->parent_event_id !== null;
    }

    public function isRecurringRoot()
    {
        return $this->isRecurring() && !$this->isRecurringInstance();
    }

    public function isRecurring()
    {
        return !empty($this->rrule);
    }
}