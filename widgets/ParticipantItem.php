<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\user\models\User;
use Yii;

/**
 * ParticipantItem widget to display a participant row of the Calendar entry for Participants List
 */
class ParticipantItem extends Widget
{
    /**
     * @var CalendarEntry
     */
    public $entry;

    /**
     * @var User
     */
    public $user;

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('participantItem', [
            'entry' => $this->entry,
            'user' => $this->user,
            'statuses' => self::getStatuses(),
        ]);
    }

    public static function getStatuses(bool $allowInvite = true): array
    {
        $statuses = [
            CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED => Yii::t('CalendarModule.views_entry_edit', 'Attend'),
            CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE => Yii::t('CalendarModule.views_entry_edit', 'Maybe'),
            CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED => Yii::t('CalendarModule.views_entry_edit', 'Declined'),
        ];

        if ($allowInvite) {
            $statuses[CalendarEntryParticipant::PARTICIPATION_STATE_INVITED] = Yii::t('CalendarModule.views_entry_edit', 'Invited');
        }

        return $statuses;
    }

    public static function hasStatus($status): bool
    {
        return array_key_exists($status, self::getStatuses());
    }

    public static function getStatusTitle($status): string
    {
        $statuses = self::getStatuses();
        return $statuses[$status] ?? '';
    }
}
