<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\jobs;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\calendar\notifications\ParticipantAdded;
use humhub\modules\queue\ActiveJob;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use yii\base\InvalidConfigException;

class ForceParticipation extends ActiveJob
{
    public $entry_id;

    public $originator_id;

    public $status;

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \Throwable
     */
    public function run()
    {
        /* @var $entry CalendarEntry */
        $entry = CalendarEntry::findOne(['id' => $this->entry_id]);
        $originator = User::findOne(['id' => $this->originator_id]);
        $status = isset($this->status) ? $this->status : CalendarEntryParticipation::PARTICIPATION_STATUS_ACCEPTED;

        if (!$entry || !$originator || !($entry->content->container instanceof Space) ||
            !CalendarEntryParticipation::isAllowedStatus($status)) {
            throw new InvalidConfigException('Could not force calendar event participation due to invalid config (' . $this->entry_id . ', ' . $this->originator_id . ', ' . $status . ')');
        }

        $subQuery = CalendarEntryParticipant::find()
            ->where(['calendar_entry_id' => $this->entry_id])
            ->andWhere('calendar_entry_participant.user_id = space_membership.user_id');

        /* @var Membership[] $remainingMemberships */
        $remainingMemberships = Membership::find()
            ->joinWith('user')
            ->where(['space_id' => $entry->content->container->id])
            ->andWhere(['space_membership.status' => Membership::STATUS_MEMBER])
            ->andWhere(['user.status' => User::STATUS_ENABLED])
            ->andWhere(['NOT EXISTS', $subQuery])->all();

        $users = [];
        foreach ($remainingMemberships as $membership) {
            if ($entry->participation->setParticipationStatus($membership->user, $status)) {
                $users[] = $membership->user;
            }
        }

        if (count($users)) {
            ParticipantAdded::instance()->from($originator)->about($entry)->sendBulk($users);
        }
    }
}
