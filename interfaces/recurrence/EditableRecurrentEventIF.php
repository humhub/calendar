<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\interfaces\recurrence;

/**
 * This interface may be used for recurrent events which facilitate the edit mechanisms of [[RecurrenceFormModel]] as:
 *
 *  - Updating a single recurrent instance
 *  - Updating all events of a recurring root event
 *  - Update a recurrent instance and following instances by splitting the recurrent instance into separate recurring events.
 *
 * Recurrent models which do not implement this interface either do not support editing or implement a custom edit behavior.
 *
 * This interface requires the implementation of the [[getRecurrenceQuery()]] function which needs to return a [[RecurrenceQueryIF]].
 * In most cases you can simply extend the [[AbstractRecurrenceQuery]] for this purpose, especially when following the database field
 * recommendations of [[RecurrentEventIF]].
 *
 * @package humhub\modules\calendar\interfaces\recurrence
 * @see RecurrenceQueryIF
 */
interface EditableRecurrentEventIF extends RecurrentEventIF
{

    /**
     * @return RecurrenceQueryIF
     */
    public function getRecurrenceQuery();
}