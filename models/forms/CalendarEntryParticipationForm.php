<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models\forms;

use DateTime;
use humhub\modules\calendar\models\reminder\forms\ReminderSettings;
use humhub\modules\content\models\Content;
use humhub\modules\content\permissions\CreatePublicContent;
use humhub\modules\space\models\Space;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use DateTimeZone;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\models\forms\validators\CalendarDateFormatValidator;
use humhub\modules\calendar\models\forms\validators\CalendarEndDateValidator;
use humhub\modules\calendar\models\forms\validators\CalendarTypeValidator;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\topic\models\Topic;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\models\CalendarEntry;

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
     * @var integer if set to true all space participants will be added to the event
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
            [['sendUpdateNotification', 'forceJoin'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sendUpdateNotification' => Yii::t('CalendarModule.base', 'Send update notification'),
            'forceJoin' => ($this->entry->isNewRecord)
                ? Yii::t('CalendarModule.base', 'Add all space members to this event')
                : Yii::t('CalendarModule.base', 'Invite all participants from the space'),
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

            if ($this->sendUpdateNotification && !$this->entry->isNewRecord) {
                $this->entry->participation->sendUpdateNotification();
            }

            if ($this->forceJoin) {
                $this->entry->participation->addAllUsers();
            }

            return true;
        });
    }

    public static function getModeItems(): array
    {
        return [
            CalendarEntry::PARTICIPATION_MODE_NONE => Yii::t('CalendarModule.views_entry_edit', 'No participants'),
            CalendarEntry::PARTICIPATION_MODE_INVITE => Yii::t('CalendarModule.views_entry_edit', 'Only by Invite'),
            CalendarEntry::PARTICIPATION_MODE_ALL => Yii::t('CalendarModule.views_entry_edit', 'Everybody can participate')
        ];
    }
}