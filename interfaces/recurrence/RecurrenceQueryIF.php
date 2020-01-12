<?php


namespace humhub\modules\calendar\interfaces\recurrence;


use DateTime;

interface RecurrenceQueryIF
{
    public function getRecurrenceRoot();

    public function getRecurrenceInstance($recurrent_id);

    public function onDelete();

    public function saveThisAndFollowing(RecurrentEventIF $original);

    public function saveAll(RecurrentEventIF $original);

    /**
     * Returns existing recurrence instances following this $this->event. Note this function should not create any instance
     * or return recurrent instances which are not persisted.
     *
     * @return RecurrentEventIF[]
     * @throws \Throwable
     */
    public function getFollowingInstances();

    public function getExistingRecurrences(DateTime $start = null, DateTime $end = null);

    public function expandSingle($recurrence_id, $save = true);

    public function getRecurrenceExceptions(DateTime $start = null, DateTime $end = null);

    public function expandEvent($from = null, $to = null, $save = false, &$expandResult = []);

    public function expandUpcoming($count, $from = null,  $save = false);
}