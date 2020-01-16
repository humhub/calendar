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
use humhub\modules\calendar\notifications\ForceAdd;
use humhub\modules\queue\ActiveJob;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;
use yii\base\InvalidConfigException;

class ForceParticipation extends ActiveJob
{
    public $entry_id;

    public $originator_id;

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \Throwable
     */
    public function run()
    {
        /* @var $entry CalendarEntry */
        $entry = CalendarEntry::findOne(['id' => $this->entry_id]);
        $originator = User::findOne(['id' => $this->originator_id]);

        if(!$entry || !$originator || !($entry->content->container instanceof Space)) {
            throw new InvalidConfigException('Could not force calendar event participation due to inavlid config ('.$this->entry_id.', '.$this.$this->originator_id.')');
        }

        $subQuery = CalendarEntryParticipant::find()
            ->where(['calendar_entry_id' => $this->entry_id])
            ->andWhere('calendar_entry_participant.user_id = space_membership.user_id');

        $remainingMemberships = Membership::find()
            ->where(['space_id' => $entry->content->container->id])
            ->andWhere(['NOT EXISTS', $subQuery])->all();

        $users = [];
        foreach ($remainingMemberships as $membership) {
            $entry->participation->setParticipationStatus($membership->user);
            $users[] = $membership->user;
        }

        ForceAdd::instance()->from($originator)->about($entry)->sendBulk($users);
    }
}
