<?php


namespace humhub\modules\calendar\interfaces;


use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\recurrence\RecurrentCalendarEventIF;
use humhub\modules\content\components\ContentActiveRecord;

/**
 * Class AbstractRecurrentEvent
 * @package humhub\modules\calendar\interfaces
 * @property integer $id
 * @property string $rrule
 * @property string recurrence_id
 * @property string exdate
 * @property string start_datetime
 * @property string end_datetime
 * @property integer parent_event_id
 */
abstract class AbstractRecurrentEvent extends ContentActiveRecord implements RecurrentCalendarEventIF
{
    public function createRecurrence($start, $end, $recurrenceId)
    {
        $instance = new static($this->content->container, $this->content->visibility);
        $instance->start_datetime = $start;
        $instance->end_datetime = $end;
        $instance->setRecurrenceId($recurrenceId);
        $instance->syncFromRecurrentRoot($this);
        return $instance;
    }

    /**
     * @param static $event
     * @return mixed|void
     */
    public function syncFromRecurrentRoot($event)
    {
        $this->setUid($event->getUid());
        $this->setRecurrenceRootId($event->getId());
        $this->setRrule($event->getRrule());
        $this->content->created_by = $event->content->created_by;
    }

    public function saveRecurrenceInstance()
    {
        return $this->save();
    }

    public function setRrule($rrule)
    {
        $this->rrule = $rrule;
    }

    public function getFollowingInstances($fromDate = null)
    {
        if($this->isRecurringRoot()) {
            $query = static::find()->where(['parent_event_id' => $this->id]);
        } else {
            // Make sure we use the original date, the start_date may have been overwritten
            $start_datetime = RecurrenceHelper::recurrenceIdToDate($this);
            $query = static::find()->where(['parent_event_id' => $this->parent_event_id])->andWhere(['>', 'start_datetime', $start_datetime]);
        }

        return $query->orderBy('start_datetime')->all();
    }

    /**
     * @return static|null
     */
    public function getRecurrenceRoot()
    {
        return static::findOne(['id' => $this->getRecurrenceRootId()]);
    }

    public function getRecurrenceId()
    {
        return $this->recurrence_id;
    }

    public function setRecurrenceId($recurrenceId)
    {
        $this->recurrence_id = $recurrenceId;
    }

    public function deleteRecurrenceInstance()
    {
        $this->delete();
    }

    public function setRecurrenceRootId($rootId)
    {
        $this->parent_event_id = $rootId;
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

    public function getRecurrenceRootId()
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