<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\notifications\base;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\notifications\CalendarNotificationCategory;
use humhub\modules\notification\components\BaseNotification;
use yii\mail\MessageInterface;

/* @property CalendarEntry $source */
abstract class EventNotification extends BaseNotification
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
