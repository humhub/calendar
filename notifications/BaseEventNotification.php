<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace  humhub\modules\calendar\notifications;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\notification\components\BaseNotification;
use yii\mail\MessageInterface;

/* @property CalendarEntry $source */
abstract class BaseEventNotification extends BaseNotification
{
    /**
     * @inheritdoc
     */
    public $moduleId = 'calendar';

    /**
     * @inheritdoc
     */
    public $viewName = 'participationInfoNotification';

    /**
     * @inheritdoc
     */
    public function category()
    {
        return new CalendarNotificationCategory();
    }

    /**
     * @inheritdoc
     */
    public function beforeMailSend(MessageInterface $message)
    {
        $ics = $this->source->generateIcs();

        if (!empty($ics)) {
            $message->attachContent($ics, [
                'fileName' => $this->source->getUid() . '.ics',
                'contentType' => 'text/calendar',
            ]);
        }

        return true;
    }
}
