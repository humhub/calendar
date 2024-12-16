<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\interfaces\event;

interface CalendarEventStatusIF
{
    public const STATUS_TENTATIVE = 'TENTATIVE';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CANCELLED = 'CANCELLED';

    /**
     * @return mixed
     */
    public function getEventStatus();

}
