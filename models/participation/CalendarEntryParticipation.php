<?php

namespace humhub\modules\calendar\models\participation;

use humhub\modules\calendar\interfaces\participation\CalendarEventParticipationIF;
use humhub\modules\calendar\jobs\ForceParticipation;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\notifications\EventUpdated;
use humhub\modules\calendar\permissions\ManageEntry;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\models\User;
use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;

class CalendarEntryParticipation extends Model implements CalendarEventParticipationIF
{
    /**
     * Participation Modes
     */
    const PARTICIPATION_MODE_NONE = 0;
    const PARTICIPATION_MODE_ALL = 2;

    /**
     * @var array all given participation modes as array
     */
    public static $participationModes = [
        self::PARTICIPATION_MODE_NONE,
        self::PARTICIPATION_MODE_ALL
    ];

    /**
     * @var CalendarEntry
     */
    public $entry;

    public function setDefautls()
    {
        $defaultSettings = new ParticipationSettings(['contentContainer' => $this->entry->content->container]);

        // Default participiation Mode
        if($this->entry->participation_mode === null) {
            $this->entry->participation_mode = $defaultSettings->participation_mode;
        }

        if($this->entry->allow_maybe === null) {
            $this->entry->allow_maybe = $defaultSettings->allow_maybe;
        }

        if($this->entry->allow_decline === null) {
            $this->entry->allow_decline = $defaultSettings->allow_decline;
        }
    }

    public function isEnabled()
    {
        return $this->entry->participation_mode != self::PARTICIPATION_MODE_NONE;
    }

    public function getParticipationStatus(User $user = null)
    {
        if (!$user) {
            return static::PARTICIPATION_STATUS_NONE;
        }

        $participant = CalendarEntryParticipant::findOne(['user_id' => $user->id, 'calendar_entry_id' => $this->entry->id]);

        if ($participant !== null) {
            return $participant->participation_state;
        }

        return CalendarEntryParticipant::PARTICIPATION_STATE_NONE;
    }

    /**
     * @param User $user
     * @param $status int
     * @throws \Throwable
     */
    public function setParticipationStatus(User $user, $status = self::PARTICIPATION_STATUS_ACCEPTED)
    {
        $participant = $this->findParticipant($user);

        if($participant && $status == self::PARTICIPATION_STATUS_NONE) {
            $participant->delete();
            return;
        }

        if (!$participant) {
            $participant = new CalendarEntryParticipant([
                'user_id' => $user->id,
                'calendar_entry_id' => $this->entry->id
            ]);
        }

        $participant->participation_state = $status;
        $participant->save();
    }

    public function getExternalParticipants($status = [])
    {
        return null;
    }

    public function getParticipants($status = [])
    {
        return $this->findParticipants($status)->all();
    }

    public function getParticipantCount($status = [])
    {
        return $this->findParticipants($status)->count();
    }

    /**
     * @param array $status
     * @return ActiveQueryUser
     */
    public function findParticipants($status = [])
    {
        if(is_int($status)) {
            $status = [$status];
        }

        if(empty($status)) {
            return $this->entry->hasMany(User::class, ['id' => 'user_id'])->via('participantEntries');
        }

        return $this->entry->hasMany(User::class, ['id' => 'user_id'])->via('participantEntries', function($query) use ($status) {
            /* @var $query ActiveQuery */
            $query->andWhere(['IN', 'calendar_entry_participant.participation_state', $status]);
        });
    }

    /**
     * @return ActiveQuery
     */
    public function getParticipantEntries()
    {
        return $this->entry->hasMany(CalendarEntryParticipant::class, ['calendar_entry_id' => 'id']);
    }

    /**
     * @param string $notificationClass
     * @throws \yii\base\InvalidConfigException
     * @throws \Throwable
     */
    public function sendUpdateNotification($notificationClass = EventUpdated::class)
    {
        $participants = $this->findParticipants([
            CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE,
            CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED])->all();

        Yii::createObject(['class' => $notificationClass])->from(Yii::$app->user->getIdentity())->about($this->entry)->sendBulk($participants);
    }

    public function afterMove(ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            return;
        }

        $spaceMemberQuery = Membership::find()
            ->where('space_membership.user_id = calendar_entry_participant.user_id')
            ->andWhere(['space_membership.space_id' => $container->id]);

        $query = CalendarEntryParticipant::find()
            ->where(['calendar_entry_id' => $this->entry->id])->andWhere(['NOT EXISTS', $spaceMemberQuery]);

        foreach ($query->all() as $nonSpaceMember) {
            try {
                $nonSpaceMember->delete();
            } catch (\Throwable $e) {
                Yii::error($e);
            }
        }
    }

    /**
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteAll()
    {
        foreach ($this->entry->participantEntries as $pariticpantEntry) {
            $pariticpantEntry->delete();
        }
    }


    /**
     * Adds all space members to this event
     */
    public function addAllUsers()
    {
        if($this->entry->participation_mode == static::PARTICIPATION_MODE_ALL && $this->canAddAll()) {
            Yii::$app->queue->push(new ForceParticipation(['entry_id' => $this->entry->id, 'originator_id' => Yii::$app->user->getId()]));
        }
    }

    /**
     * @return bool
     */
    public function canAddAll()
    {
        return  $this->entry->content->container instanceof Space
            && $this->entry->content->container->can(ManageEntry::class);
    }



    /**
     * Finds a participant instance for the given user or the logged in user if no user provided.
     *
     * @param User $user
     * @return CalendarEntryParticipant
     * @throws \Throwable
     */
    public function findParticipant(User $user = null)
    {
        if (!$user) {
            $user = Yii::$app->user->getIdentity();
        }

        if(!$user) {
            return;
        }

        return CalendarEntryParticipant::findOne(['user_id' => $user->id, 'calendar_entry_id' => $this->entry->id]);
    }

    /**
     * Checks if given or current user can respond to this event
     *
     * @param User $user
     * @return boolean
     * @throws \Throwable
     */
    public function canRespond(User $user = null)
    {
        if (!$user) {
            return false;
        }

        if($this->entry->closed) {
            return false;
        }

        // Participants always can change/reset their state
        if($this->isParticipant()) {
            return true;
        }

        if(!$this->maxParticipantCheck()) {
            return false;
        }

        if ($this->entry->participation_mode == self::PARTICIPATION_MODE_ALL) {
            return true;
        }

        return false;
    }

    private function maxParticipantCheck()
    {
        return empty($this->entry->max_participants)
            || ($this->getParticipantCount(CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED) < $this->entry->max_participants);
    }

    public function isParticipant(User $user = null, $includeMaybe = true)
    {
        $states = $includeMaybe
            ?  [static::PARTICIPATION_STATUS_ACCEPTED, static::PARTICIPATION_STATUS_MAYBE]
            :  [static::PARTICIPATION_STATUS_ACCEPTED];

        return in_array($this->getParticipationStatus($user), $states);
    }

    public function isShowParticipationInfo(User $user = null)
    {
        if(empty($this->entry->participant_info)) {
            return false;
        }

        return $this->isParticipant($user);
    }

    /**
     * @return User
     */
    public function getOrganizer()
    {
        return $this->entry->getOwner();
    }
}