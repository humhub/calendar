<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\notifications;

use humhub\modules\calendar\notifications\base\EventNotification;
use Yii;

/**
 * Notifies the participants that a canceled event was reopened.
 */
class ReopenedEvent extends EventNotification
{
    /**
     * @inheritdoc
     */
    protected function getMessage(array $params): string
    {
        if ($this->getSpace() !== null) {
            return Yii::t('CalendarModule.notification', '{displayName} reopened the event "{contentTitle}" in the space {spaceName}.', $params);
        }

        return Yii::t('CalendarModule.notification', '{displayName} reopened the event "{contentTitle}".', $params);
    }

    /**
     * @inheritdoc
     */
    protected function getRedactedMessage(array $params): ?string
    {
        return Yii::t('CalendarModule.notification', '{displayName} reopened an event.', $params);
    }

    /**
     * @inheritdoc
     */
    protected function getMailSubject(array $params): string
    {
        return Yii::t('CalendarModule.notification', '{displayName} reopened the event "{contentTitle}".', $params);
    }
}
