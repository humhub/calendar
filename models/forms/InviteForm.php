<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models\forms;

use humhub\modules\calendar\models\CalendarEntryParticipant;
use Yii;
use yii\base\Model;

/**
 * Form to invite new participants into the Calendar entry
 *
 * @property-read array $modes
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
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['entryId'], 'integer'],
            [['entryId', 'userGuids', 'mode'], 'required'],
        ];
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

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        // TODO: Add participants

        return true;
    }
}