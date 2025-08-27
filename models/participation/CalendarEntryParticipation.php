<?php

namespace humhub\modules\calendar\models\participation;

use humhub\components\export\SpreadsheetExport;
use humhub\modules\admin\permissions\ManageUsers;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\participation\CalendarEventParticipationIF;
use humhub\modules\calendar\jobs\ForceParticipation;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\notifications\EventUpdated;
use humhub\modules\calendar\permissions\ManageEntry;
use humhub\modules\calendar\widgets\ParticipantFilter;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\models\User;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\web\Response;

class CalendarEntryParticipation extends Model implements CalendarEventParticipationIF
{
    /**
     * Participation Modes
     */
    public const PARTICIPATION_MODE_NONE = 0;
    public const PARTICIPATION_MODE_INVITE = 1;
    public const PARTICIPATION_MODE_ALL = 2;

    /**
     * @var array all given participation modes as array
     */
    public static $participationModes = [
        self::PARTICIPATION_MODE_NONE,
        self::PARTICIPATION_MODE_INVITE,
        self::PARTICIPATION_MODE_ALL,
    ];

    /**
     * @var CalendarEntry
     */
    public $entry;

    public function setDefautls()
    {
        $defaultSettings = new ParticipationSettings(['contentContainer' => $this->entry->content->container]);

        // Default participiation Mode
        if ($this->entry->participation_mode === null) {
            $this->entry->participation_mode = $defaultSettings->participation_mode;
        }

        if ($this->entry->allow_maybe === null) {
            $this->entry->allow_maybe = $defaultSettings->allow_maybe;
        }

        if ($this->entry->allow_decline === null) {
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
     * @return bool
     * @throws \Throwable
     */
    public function setParticipationStatus(User $user, $status = self::PARTICIPATION_STATUS_ACCEPTED): bool
    {
        $participant = $this->findParticipant($user);

        if ($participant && $status == self::PARTICIPATION_STATUS_NONE) {
            $participant->delete();
            return true;
        }

        if (!$participant) {
            if (!$this->entry->content->canView($user)) {
                return false;
            }
            $participant = new CalendarEntryParticipant([
                'user_id' => $user->id,
                'calendar_entry_id' => $this->entry->id,
            ]);
        }

        $participant->participation_state = $status;
        return $participant->save();
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
        if (is_int($status)) {
            $status = [$status];
        }

        if (empty($status)) {
            return $this->entry->hasMany(User::class, ['id' => 'user_id'])->via('participantEntries');
        }

        return $this->entry->hasMany(User::class, ['id' => 'user_id'])->via('participantEntries', function ($query) use ($status) {
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
            CalendarEntryParticipant::PARTICIPATION_STATE_INVITED,
            CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE,
            CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED])->all();

        Yii::createObject(['class' => $notificationClass])->from(Yii::$app->user->getIdentity())->about($this->entry)->sendBulk($participants);
    }

    public function afterMove(ContentContainerActiveRecord $container = null)
    {
        if (!$container) {
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
     *
     * @param int|null $status NUUL - Default status - static::PARTICIPATION_STATUS_ACCEPTED
     */
    public function addAllUsers($status = null)
    {
        if ($this->entry->participation_mode != static::PARTICIPATION_MODE_NONE && $this->canAddAll()) {
            Yii::$app->queue->push(new ForceParticipation([
                'entry_id' => $this->entry->id,
                'originator_id' => Yii::$app->user->getId(),
                'status' => $status,
            ]));
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
     * @return CalendarEntryParticipant|null
     * @throws \Throwable
     */
    public function findParticipant(User $user = null): ?CalendarEntryParticipant
    {
        if (!$user) {
            $user = Yii::$app->user->getIdentity();
        }

        if (!$user) {
            return null;
        }

        return CalendarEntryParticipant::findOne(['user_id' => $user->id, 'calendar_entry_id' => $this->entry->id]);
    }

    /**
     * Checks if given or current user can respond to this event
     *
     * @param User $user
     * @return bool
     * @throws \Throwable
     */
    public function canRespond(User $user = null)
    {
        if ($this->entry->participation_mode == self::PARTICIPATION_MODE_NONE) {
            return false;
        }

        if (RecurrenceHelper::isRecurrentRoot($this->entry)) {
            return false;
        }

        if (!$user) {
            return false;
        }

        if ($this->entry->closed) {
            return false;
        }

        if ($this->entry->isOwner($user)) {
            return true;
        }

        if ($this->isInvited($user)) {
            return true;
        }

        // Participants always can change/reset their state
        if ($this->isParticipant($user)) {
            return true;
        }

        if (!$this->maxParticipantCheck()) {
            return false;
        }

        if ($this->entry->participation_mode == self::PARTICIPATION_MODE_ALL) {
            return true;
        }

        return false;
    }

    public function maxParticipantCheck()
    {
        return empty($this->entry->max_participants)
            || ($this->getParticipantCount(CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED) < $this->entry->max_participants);
    }

    public function isParticipant(User $user = null, $includeMaybe = true)
    {
        $states = $includeMaybe
            ? [static::PARTICIPATION_STATUS_ACCEPTED, static::PARTICIPATION_STATUS_MAYBE]
            : [static::PARTICIPATION_STATUS_ACCEPTED];

        return in_array($this->getParticipationStatus($user), $states);
    }

    public static function isAllowedStatus(int $status): bool
    {
        return in_array($status, [
            static::PARTICIPATION_STATUS_NONE,
            static::PARTICIPATION_STATUS_DECLINED,
            static::PARTICIPATION_STATUS_MAYBE,
            static::PARTICIPATION_STATUS_ACCEPTED,
            static::PARTICIPATION_STATUS_INVITED,
        ]);
    }

    public function isInvited(User $user = null)
    {
        if ($user === null && !Yii::$app->user->isGuest) {
            $user = Yii::$app->user->getIdentity();
        }

        return $this->getParticipationStatus($user) === static::PARTICIPATION_STATUS_INVITED;
    }

    public function isShowParticipationInfo(User $user = null)
    {
        if (empty($this->entry->participant_info) || !$this->isEnabled()) {
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

    public function exportParticipants(?int $state, string $type): Response
    {
        $dataProvider = new ActiveDataProvider([
            'query' => $this->findParticipants($state)->joinWith('profile'),
        ]);

        $statuses = ParticipantFilter::getStatuses();

        $columns = [
            'username',
            'profile.firstname',
            'profile.lastname',
            [
                'label' => Yii::t('CalendarModule.base', 'Participation Status'),
                'value' => function (User $user) use ($statuses) {
                    return $statuses[$this->getParticipationStatus($user)] ?? '';
                },
            ],
        ];
        if (!Yii::$app->user->isGuest && Yii::$app->user->can(ManageUsers::class)) {
            $columns[] = [
                'label' => Yii::t('CalendarModule.base', 'Email'),
                'value' => function (User $user) {
                    return $user->email;
                },
            ];
            $columns[] = 'profile.gender';
            $columns[] = 'profile.city';
            $columns[] = 'profile.country';
        }

        $exporter = new SpreadsheetExport([
            'dataProvider' => $dataProvider,
            'columns' => $columns,
            'resultConfig' => [
                'fileBaseName' => Yii::t('CalendarModule.base', 'Participants')
                    . (isset($statuses[$state]) ? '-' . $statuses[$state] : '')
                    . '-' . $this->entry->title,
                'writerType' => $type,
            ],
        ]);

        return $exporter->export()->send();
    }
}
