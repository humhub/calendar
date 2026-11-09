<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\notifications;

use humhub\modules\calendar\notifications\base\EventNotification;
use Yii;

/**
 * Confirms to a user that they registered for the event.
 */
class MarkAttend extends EventNotification
{
    /**
     * @inheritdoc
     */
    protected bool $withParticipantInfo = true;

    /**
     * @inheritdoc
     */
    protected function getMessage(array $params): string
    {
        if ($this->getSpace() !== null) {
            return Yii::t('CalendarModule.base', 'You have been registered for the event "{contentTitle}" in {spaceName}, starting at {time}', $params);
        }

        return Yii::t('CalendarModule.base', 'You have been registered for the event "{contentTitle}", starting at {time}.', $params);
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
        return Yii::t('CalendarModule.base', 'You have been registered for an event.');
    }

    /**
     * @inheritdoc
     */
    protected function getMailSubject(array $params): string
    {
        return Yii::t('CalendarModule.base', 'You have been registered for the event "{contentTitle}".', $params);
    }
}
