<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\interfaces;


interface RecurrentCalendarEntryIF extends CalendarEntryIF
{
    /**
     * @return string
     */
    public function getRrule();

    /**
     * @return string
     */
    public function getExdate();

}