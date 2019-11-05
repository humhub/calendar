<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\interfaces;


use humhub\modules\content\models\Content;
use humhub\modules\user\components\ActiveQueryUser;

interface Remindable extends CalendarEntryIF
{
    /**
     * @return Content
     */
    public function getContentRecord();

    /**
     * Returns a ActiveQueryUser including all users which should receive a reminder.
     *
     * @return ActiveQueryUser
     */
    public function findUsersByInterest();
}