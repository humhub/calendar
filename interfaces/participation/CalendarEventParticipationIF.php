<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\interfaces\participation;


use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\models\User;

interface CalendarEventParticipationIF
{
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

    /**
     * Array of external participant emails
     *
     * @param array $status
     * @return string[]
     */
    public function getExternalParticipants($status = []);

    /**
     * @param array $status
     * @return mixed
     */
    public function getParticipants($status = []);

    /**
     * @param array $status
     * @return ActiveQueryUser
     */
    public function findParticipants($status = []);
    public function getParticipantCount($status = []);

    /**
     * @return User
     */
    public function getOrganizer();

}