<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\notifications;

use humhub\modules\calendar\interfaces\participation\CalendarEventParticipationIF;
use humhub\modules\calendar\notifications\base\EventNotification;
use Yii;

/**
 * Notifies a user that they were invited or added to the event.
 */
class ParticipantAdded extends EventNotification
{
    /**
     * @inheritdoc
     */
    protected bool $withParticipantInfo = true;

    public ?int $participationStatus = null;

    /**
     * @inheritdoc
     */
    protected function getMessage(array $params): string
    {
        $invited = $this->isInvited();

        if ($this->getSpace() !== null) {
            return $invited
                ? Yii::t('CalendarModule.base', '{displayName} invited you to the event "{contentTitle}" in the space {spaceName}, starting at {time}.', $params)
                : Yii::t('CalendarModule.base', '{displayName} added you to the event "{contentTitle}" in the space {spaceName}, starting at {time}.', $params);
        }

        return $invited
            ? Yii::t('CalendarModule.base', '{displayName} invited you to the event "{contentTitle}", starting at {time}.', $params)
            : Yii::t('CalendarModule.base', '{displayName} added you to the event "{contentTitle}", starting at {time}.', $params);
    }

    /**
     * @inheritdoc
     */
    protected function getMessageParams(): array
    {
        return ['time' => $this->getFormattedTime()];
    }

    /**
     * @inheritdoc
     */
    protected function getRedactedMessage(array $params): ?string
    {
        return $this->isInvited()
            ? Yii::t('CalendarModule.base', '{displayName} invited you to an event.', $params)
            : Yii::t('CalendarModule.base', '{displayName} added you to an event.', $params);
    }

    /**
     * @inheritdoc
     */
    protected function getMailSubject(array $params): string
    {
        return $this->isInvited()
            ? Yii::t('CalendarModule.base', '{displayName} invited you to the event "{contentTitle}".', $params)
            : Yii::t('CalendarModule.base', '{displayName} added you to the event "{contentTitle}".', $params);
    }

    private function isInvited(): bool
    {
        $entry = $this->getEntry();
        if ($entry === null) {
            return false;
        }

        $this->participationStatus ??= (int)$entry->participation->getParticipationStatus($this->recipient);

        return $this->participationStatus === CalendarEventParticipationIF::PARTICIPATION_STATUS_INVITED;
    }
}
