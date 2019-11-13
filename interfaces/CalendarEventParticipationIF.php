<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\interfaces;


use humhub\modules\user\models\User;

interface CalendarEventParticipationIF
{
    const ICS_PARTICIPATION_STATUS_ACCEPTED = 'ACCEPTED';
    const ICS_PARTICIPATION_STATUS_DECLINED = 'DECLINED';
    const ICS_PARTICIPATION_STATUS_TENTATIVE = 'TENTATIVE';

    const PARTICIPATION_STATUS_NONE = 0;
    const PARTICIPATION_STATUS_DECLINED = 1;
    const PARTICIPATION_STATUS_MAYBE = 2;
    const PARTICIPATION_STATUS_ACCEPTED = 3;

    /**
     * Returns the participation state for a given user or guests if $user is null.
     *
     * @param User $user
     * @return int
     */
    public function getParticipationStatus(User $user = null);

    /**
     * @param User|null $user
     * @return mixed
     */
    public function canRespond(User $user = null);

    /**
     * @param User $user
     * @param $status int
     * @return
     */
    public function setParticipationStatus(User $user, $status = self::PARTICIPATION_STATUS_ACCEPTED);
    public function getExternalParticipants($status = []);
    public function getParticipants($status = []);
    public function getParticipantCount($status = []);

}