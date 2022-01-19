<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models\forms;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\user\models\User;
use Yii;
use yii\base\Model;

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
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['entryId', 'userGuids', 'mode'], 'required'],
            [['entryId'], 'integer'],
            [['entryId'], 'validateEntryId'],
            [['mode'], 'validateMode'],
        ];
    }

    public function validateEntryId()
    {
        if (!isset($this->modes[$this->mode])) {
            $this->addError('mode', 'Wrong mode!');
        }
    }

    public function validateMode()
    {
        if ($this->entry && !$this->entry->isOwner()) {
            $this->addError('entryId', 'Only owner of the calendar entry can invite!');
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

        return CalendarEntry::findOne($this->entryId);
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $users = User::find()->where(['IN', 'guid', $this->userGuids])->all();
        foreach ($users as $user) {
            $this->entry->setParticipationStatus($user, $this->mode);
        }

        return true;
    }
}