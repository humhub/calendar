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
    /**
     * @return string
     */
    public function getRrule();

    public function setRrule($rrule);

    public function getRecurrenceId();

    public function setRecurrenceId($recurrenceId);

    public function setUid($uid);

    /**
     * @return static[]
     */
    public function getFollowingInstances();

    /**
     * @param $root
     * @param $start
     * @param $end
     * @param $recurrenceId
     * @return mixed
     */
    public function createRecurrence($start, $end, $recurrenceId);

    public function saveRecurrenceInstance();

    public function deleteRecurrenceInstance();

    /**
     * @param $event static
     * @return mixed
     */
    public function syncFromRecurrentRoot($event);

    /**
     * @return string|null
     */
    public function getExdate();

    public function setExdate($exdateStr);

    public function getId();

    public function getRecurrenceRootId();
    public function setRecurrenceRootId($rootId);

    /**
     * @return RecurrentEventIF
     */
    public function getRecurrenceRoot();

    public function getRecurrenceViewUrl($cal = false);

    /**
     * @return Content
     */
    public function getContentRecord();
}