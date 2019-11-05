<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\interfaces;


use humhub\modules\user\models\User;

interface CalendarEntryParticipation
{
    const STATUS_ACCEPTED = 'ACCEPTED';
    const STATUS_DECLINED = 'DECLINED';
    const STATUS_TENTATIVE = 'TENTATIVE';

    public function getState(User $user);
    public function setState(User $user, $status);
    public function getExternalParticipants($status);
    public function getParticipants($status);
}