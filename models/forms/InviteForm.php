<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models\forms;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\notifications\Invited;
use humhub\modules\user\models\User;
use Yii;
use yii\base\Model;
use yii\db\Expression;

/**
 * Form to invite new participants into the Calendar entry
 *
 * @property-read array $modes
 * @property-read CalendarEntry $entry
 */
class InviteForm extends Model
{
    /**
     * @var int
     */
    public $entryId;

    /**
     * @var array
     */
    public $userGuids;

    /**
     * @var string
     */
    public $mode;

    /**
     * @var CalendarEntry|null Cached calendar entry
     */
    public $_entry;

    /**
     * @var User[]
     */
    public $invitedUsers;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['entryId', 'userGuids'], 'required'],
            [['entryId'], 'integer'],
            [['entryId'], 'validateEntryId'],
            [['mode'], 'validateMode'],
        ];
    }

    public function validateEntryId()
    {
        if ($this->entry && !$this->entry->isOwner()) {
            $this->addError('entryId', 'Only owner of the calendar entry can invite!');
        }
    }

    public function validateMode()
    {
        if (!isset($this->modes[$this->mode])) {
            $this->addError('mode', 'Wrong mode!');
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'userGuids' => Yii::t('CalendarModule.base', 'New participants'),
            'mode' => Yii::t('CalendarModule.base', 'Mode'),
        ];
    }

    public function getModes(): array
    {
        return [
            CalendarEntryParticipant::PARTICIPATION_STATE_NONE => Yii::t('CalendarModule.base', 'None'),
            CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED => Yii::t('CalendarModule.base', 'Attend'),
            CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE => Yii::t('CalendarModule.base', 'Maybe'),
            CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED => Yii::t('CalendarModule.base', 'Decline'),
        ];
    }

    public function getEntry(): ?CalendarEntry
    {
        if (empty($this->entryId)) {
            return null;
        }

        if ($this->_entry === null) {
            $this->_entry = CalendarEntry::findOne($this->entryId);
            if (!$this->_entry) {
                $this->_entry = false;
            }
        }

        return $this->_entry === false ? null : $this->_entry;
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->invitedUsers = User::find()
            ->leftJoin('calendar_entry_participant', 'user.id = user_id AND calendar_entry_id = :entry_id', ['entry_id' => $this->entry->id])
            ->where(['IN', 'guid', $this->userGuids])
            ->andWhere(['IS', 'user_id', new Expression('NULL')])
            ->all();

        if (empty($this->invitedUsers)) {
            return true;
        }

        foreach ($this->invitedUsers as $invitedUser) {
            $this->entry->setParticipationStatus($invitedUser, CalendarEntryParticipant::PARTICIPATION_STATE_INVITED);
        }

        Invited::instance()->from(Yii::$app->user->getIdentity())->about($this->entry)->sendBulk($this->invitedUsers);

        return true;
    }
}