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
    public function save();
    public function delete();
}