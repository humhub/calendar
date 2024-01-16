<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace  humhub\modules\calendar\notifications;

use humhub\libs\Html;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\notification\components\BaseNotification;
use Yii;
use yii\mail\MessageInterface;

/**
 * @var $source CalendarEntry
 */
class ForceAdd extends BaseNotification
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
        return Yii::t('CalendarModule.base', '{displayName} just added you to event "{contentTitle}" in space {spaceName} starting at {time}.', [
            'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
            'contentTitle' => $this->getContentInfo($this->source, false),
            'spaceName' =>  Html::encode($this->source->content->container->displayName),
            'time' => $this->source->getFormattedTime()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject()
    {
        return Yii::t('CalendarModule.notifications_views_CanceledEvent', '{displayName} just added you to event "{contentTitle}".', [
            'displayName' =>  Html::encode($this->originator->displayName),
            'contentTitle' => $this->getContentInfo($this->source, false)
        ]);
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
}
