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
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
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

    /**
     * Get possible statuses for participation
     *
     * @param CalendarEntry|null $entry The entry is used to restrict statuses depending on Entry settings or permissions of current User,
     *                                  null - to don't restrict statuses
     * @param array|int|null $exclude What statuses should be excluded
     * @return array
     */
    public static function getStatuses(?CalendarEntry $entry = null, $exclude = null): array
    {
        if ($entry && $entry->participation_mode == CalendarEntryParticipation::PARTICIPATION_MODE_INVITE) {
            $statuses = [];
        } else {
            $statuses = [
                CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED => Yii::t('CalendarModule.views', 'Attending'),
                CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE => Yii::t('CalendarModule.views', 'Undecided'),
                CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED => Yii::t('CalendarModule.views', 'Declined'),
            ];
        }

        if (!$entry || $entry->canInvite()) {
            $statuses[CalendarEntryParticipant::PARTICIPATION_STATE_INVITED] = Yii::t('CalendarModule.views', 'Invited');
        }

        if (!empty($exclude) && !empty($statuses)) {
            if (!is_array($exclude)) {
                $exclude = [$exclude];
            }
            foreach ($exclude as $excludeStatus) {
                if (isset($statuses[$excludeStatus])) {
                    unset($statuses[$excludeStatus]);
                }
            }
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
