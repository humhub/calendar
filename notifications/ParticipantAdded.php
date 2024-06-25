<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace  humhub\modules\calendar\notifications;

use humhub\libs\Html;
use humhub\modules\calendar\interfaces\participation\CalendarEventParticipationIF;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\notification\components\BaseNotification;
use Yii;
use yii\mail\MessageInterface;

class ParticipantAdded extends BaseNotification
{

    /**
     * @var CalendarEntry
     */
    public $source;

    /**
     * @inheritdoc
     */
    public $moduleId = 'calendar';

    /**
     * @inheritdoc
     */
    public $viewName = 'participationInfoNotification';

    public ?int $participationStatus = null;

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
    public function html()
    {
        $params = [
            'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
            'contentTitle' => $this->getContentInfo($this->source, false),
            'spaceName' =>  Html::encode($this->source->content->container->displayName),
            'time' => $this->source->getFormattedTime()
        ];

        return $this->isInvited()
            ? Yii::t('CalendarModule.base', '{displayName} invited you to the event "{contentTitle}" in the space {spaceName}, starting at {time}.', $params)
            : Yii::t('CalendarModule.base', '{displayName} added you to the event "{contentTitle}" in the space {spaceName}, starting at {time}.', $params);
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject()
    {
        $params = [
            'displayName' =>  Html::encode($this->originator->displayName),
            'contentTitle' => $this->getContentInfo($this->source, false)
        ];

        return $this->isInvited()
            ? Yii::t('CalendarModule.base', '{displayName} invited you to the event "{contentTitle}".', $params)
            : Yii::t('CalendarModule.base', '{displayName} added you to the event "{contentTitle}".', $params);
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
                'contentType' => 'text/calendar'
            ]);
        }

        return true;
    }

    private function isInvited(): bool
    {
        if ($this->participationStatus === null) {
            $this->participationStatus = (int) $this->source->participation->getParticipationStatus($this->record->user);
        }

        return $this->participationStatus === CalendarEventParticipationIF::PARTICIPATION_STATUS_INVITED;
    }
}
