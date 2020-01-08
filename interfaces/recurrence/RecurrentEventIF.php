<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\interfaces\recurrence;


use DateTime;
use humhub\modules\content\models\Content;
use humhub\modules\calendar\interfaces\CalendarEventIF;

interface RecurrentEventIF extends CalendarEventIF
{
    public function getId();

    public function getRrule();
    public function setRrule($rrule);

    public function getRecurrenceRootId();
    public function setRecurrenceRootId($rootId);

    public function getRecurrenceId();
    public function setRecurrenceId($recurrenceId);

    /**
     * @return string|null
     */
    public function getExdate();

    public function setExdate($exdateStr);

    /**
     * @return AbstractRecurrenceQuery
     */
    public function getRecurrenceQuery();

    /**
     * @param $event static
     * @return mixed
     */
    public function syncEventData($event);

    /**
     * @param $root
     * @param $start
     * @param $end
     * @param $recurrenceId
     * @return mixed
     */
    public function createRecurrence($start, $end, $recurrenceId);
}