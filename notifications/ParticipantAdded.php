<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace  humhub\modules\calendar\notifications;

use humhub\libs\Html;
use humhub\modules\calendar\interfaces\participation\CalendarEventParticipationIF;
use Yii;

class ParticipantAdded extends BaseEventNotification
{
    public ?int $participationStatus = null;

    /**
     * @inheritdoc
     */
    public function html()
    {
        $params = [
            'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
            'contentTitle' => $this->getContentInfo($this->source, false),
            'spaceName' =>  Html::encode($this->source->content->container->displayName),
            'time' => $this->source->getFormattedTime(),
        ];

        return $this->isInvited()
            ? Yii::t('CalendarModule.base', '{displayName} just invited you to event "{contentTitle}" in space {spaceName} starting at {time}.', $params)
            : Yii::t('CalendarModule.base', '{displayName} just added you to event "{contentTitle}" in space {spaceName} starting at {time}.', $params);
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject()
    {
        $params = [
            'displayName' =>  Html::encode($this->originator->displayName),
            'contentTitle' => $this->getContentInfo($this->source, false),
        ];

        return $this->isInvited()
            ? Yii::t('CalendarModule.base', '{displayName} just invited you to event "{contentTitle}".', $params)
            : Yii::t('CalendarModule.base', '{displayName} just added you to event "{contentTitle}".', $params);
    }

    private function isInvited(): bool
    {
        if ($this->participationStatus === null) {
            $this->participationStatus = (int) $this->source->participation->getParticipationStatus($this->record->user);
        }

        return $this->participationStatus === CalendarEventParticipationIF::PARTICIPATION_STATUS_INVITED;
    }
}
