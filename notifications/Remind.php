<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\notifications;

use humhub\modules\calendar\notifications\base\EventNotification;
use humhub\modules\notification\components\MessageParam;
use humhub\modules\notification\components\NotificationPriority;
use Yii;

/**
 * Reminds a user of an upcoming event, see {@see \humhub\modules\calendar\models\reminder\ReminderProcessor}.
 */
class Remind extends EventNotification
{
    /**
     * A reminder is due when it is sent: no adaptive delay of the delivery.
     *
     * @inheritdoc
     */
    public static function priority(): NotificationPriority
    {
        return NotificationPriority::High;
    }

    /**
     * @inheritdoc
     */
    protected function getMessage(array $params): string
    {
        if (isset($params['contentTitle'])) {
            return Yii::t('CalendarModule.base', 'You have an {type} coming up: {contentTitle}', $params);
        }

        return Yii::t('CalendarModule.base', 'You have an {type} coming up', $params);
    }

    /**
     * @inheritdoc
     */
    protected function getMessageParams(): array
    {
        return ['type' => MessageParam::emphasis($this->getEventTypeTitle())];
    }

    /**
     * @inheritdoc
     */
    protected function getRedactedMessage(array $params): ?string
    {
        return Yii::t('CalendarModule.base', 'You have an event coming up.');
    }

    /**
     * @inheritdoc
     */
    protected function getMailSubject(array $params): string
    {
        if (isset($params['contentTitle'])) {
            return Yii::t('CalendarModule.base', 'Upcoming {type}: {title}', [
                'type' => $this->getEventTypeTitle(),
                'title' => $params['contentTitle'],
            ]);
        }

        return Yii::t('CalendarModule.base', 'Upcoming {type}', ['type' => $this->getEventTypeTitle()]);
    }

    /**
     * The title of the type of the event, "Event" without one.
     */
    public function getEventTypeTitle(): string
    {
        $title = $this->getEvent()?->getEventType()?->getTitle();

        return !empty($title) ? $title : Yii::t('CalendarModule.base', 'Event');
    }
}
