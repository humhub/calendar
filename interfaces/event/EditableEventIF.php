<?php


namespace humhub\modules\calendar\interfaces\event;


/**
 * In order to use some calendar interfaces events need to be updated or even deleted. Event types facilitating
 * such interfaces need to implement the EditableEventIF.
 *
 * @package humhub\modules\calendar\interfaces\event
 */
interface EditableEventIF extends CalendarEventIF
{
    /**
     * Sets the uid of this event. This only have to be implemented if the module does not generate own UIDs.
     * If not actually implemented (empty) a new UID is created every time this event is exported.
     *
     * @param $uid
     * @return mixed
     */
    public function setUid($uid);


    /**
     * Sets the event revision sequence, this is optional and can be implemented as empty function if not supported.
     *
     * The calendar interface increments the sequence automatically when an EditableEventIF content entry is saved
     * or when using the [[RecurrenceFormModel]].
     *
     * @param $sequence
     * @return mixed
     */
    public function setSequence($sequence);

    /**
     * Should update all data used by the event interface setter.
     *
     * @return bool|int
     */
    public function saveEvent();
}