<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models\forms;

use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\calendar\notifications\ParticipantAdded;
use humhub\modules\calendar\widgets\ParticipantItem;
use humhub\modules\user\models\User;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\calendar\models\CalendarEntry;
use Yii;
use yii\base\Model;
use yii\db\Expression;

/**
 * CalendarEntryParticipationForm to edit participation settings of the Calendar Entry
 */
class CalendarEntryParticipationForm extends Model
{
    /**
     * @var bool
     */
    public $sendUpdateNotification = 0;

    /**
     * @var int if set to true all space participants will be added to the event
     */
    public $forceJoin = 0;

    /**
     * @var CalendarEntry
     */
    public $entry;

    /**
     * @var CalendarEntry
     */
    public $original;

    /**
     * @var array
     */
    public $newParticipants;

    /**
     * @var int
     */
    public $newParticipantStatus;

    /**
     * @var int
     */
    public $newForceStatus;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->setDefaults();
    }

    private function setDefaults()
    {
        if (!$this->entry->isNewRecord) {
            $this->original = CalendarEntry::findOne(['id' => $this->entry->id]);
        } else {
            $this->entry->setDefaults();
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sendUpdateNotification', 'forceJoin', 'newParticipantStatus', 'newForceStatus'], 'integer'],
            [['newParticipants'], 'safe'],
            [['newParticipantStatus', 'newForceStatus'], 'in', 'range' => array_keys(ParticipantItem::getStatuses($this->entry, CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE))],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sendUpdateNotification' => Yii::t('CalendarModule.base', 'Notify participants about changes'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function load($data, $formName = null)
    {
        return parent::load($data) && $this->entry->load($data);
    }

    /**
     * @return bool
     * @throws \Throwable
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        return CalendarEntry::getDb()->transaction(function ($db) {
            if (!$this->entry->saveEvent()) {
                return false;
            }

            // Patch for https://github.com/humhub/humhub/issues/4847 in 1.8.beta1
            if ($this->entry->participant_info) {
                RichText::postProcess($this->entry->participant_info, $this->entry);
            }

            if ($this->sendUpdateNotification && !$this->entry->isNewRecord && !$this->entry->closed) {
                $this->entry->participation->sendUpdateNotification();
            }

            $this->addParticipants();

            if ($this->forceJoin) {
                $this->entry->participation->addAllUsers($this->newForceStatus);
            }

            return true;
        });
    }

    public static function getModeItems(): array
    {
        return [
            CalendarEntryParticipation::PARTICIPATION_MODE_NONE => Yii::t('CalendarModule.views', 'No participants'),
            CalendarEntryParticipation::PARTICIPATION_MODE_INVITE => Yii::t('CalendarModule.views', 'Only by Invite'),
            CalendarEntryParticipation::PARTICIPATION_MODE_ALL => Yii::t('CalendarModule.views', 'Everybody can participate'),
        ];
    }

    private function addParticipants(): void
    {
        if (empty($this->newParticipants)) {
            return;
        }

        $users = User::find()
            ->leftJoin('calendar_entry_participant', 'user.id = user_id AND calendar_entry_id = :entry_id', ['entry_id' => $this->entry->id])
            ->where(['IN', 'guid', $this->newParticipants])
            ->andWhere(['IS', 'user_id', new Expression('NULL')])
            ->all();

        if (empty($users)) {
            return;
        }

        foreach ($users as $u => $user) {
            if (!$this->entry->participation->setParticipationStatus($user, $this->newParticipantStatus)) {
                unset($users[$u]);
            }
        }

        if ($this->newParticipantStatus == CalendarEntryParticipant::PARTICIPATION_STATE_INVITED && count($users)) {
            ParticipantAdded::instance()->from(Yii::$app->user->getIdentity())->about($this->entry)->sendBulk($users);
        }
    }
}
