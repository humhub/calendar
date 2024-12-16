<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\notifications;

use humhub\libs\Html;
use humhub\modules\calendar\notifications\base\EventNotification;
use humhub\modules\space\models\Space;
use Yii;

class MarkAttend extends EventNotification
{
    public ?int $participationStatus = null;

    /**
     * @inheritdoc
     */
    public $suppressSendToOriginator = false;

    /**
     * @inheritdoc
     */
    public function html()
    {
        $params = [
            'contentTitle' => $this->getContentInfo($this->source, false),
            'time' => $this->source->getFormattedTime(),
        ];

        if ($this->source->content->container instanceof Space) {
            return Yii::t('CalendarModule.base', 'You have been registered for the event "{contentTitle}" in {spaceName}, starting at {time}', array_merge($params, [
                'spaceName' => Html::encode($this->source->content->container->displayName),
            ]));
        }

        return Yii::t('CalendarModule.base', 'You have been registered for the event "{contentTitle}", starting at {time}.', $params);
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject()
    {
        return Yii::t('CalendarModule.base', 'You have been registered for the event "{contentTitle}".', [
            'contentTitle' => $this->getContentInfo($this->source, false),
        ]);
    }
}
