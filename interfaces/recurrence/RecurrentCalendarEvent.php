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

interface RecurrentCalendarEvent extends CalendarEventIF
{
    /**
     * @return string
     */
    public function getRrule();

    public function setRrule($rrule);

    public function getRecurrenceId();

    public function setRecurrenceId($recurrenceId);

    /**
     * @param $root
     * @param $start
     * @param $end
     * @param $recurrenceId
     * @return mixed
     */
    public function createRecurrence($start, $end, $recurrenceId);

    /**
     * @return string|null
     */
    public function getExdate();

    public function getId();

    public function getParentId();

    public function getRecurrenceViewUrl($cal = false);

    /**
     * @return Content
     */
    public function getContentRecord();
}