<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\notifications;

use humhub\libs\Html;
use humhub\modules\calendar\notifications\base\EventNotification;
use humhub\modules\space\models\Space;
use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 21.07.2017
 * Time: 23:12
 */
class ReopenedEvent extends EventNotification
{
    /**
     * @inheritdoc
     */
    public $viewName = 'calendarNotification';

    /**
     * @inheritdoc
     */
    public function html()
    {
        $params = [
            'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
            'contentTitle' => $this->getContentInfo($this->source, false),
        ];

        if ($this->source->content->container instanceof Space) {
            return Yii::t('CalendarModule.notification', '{displayName} reopened the event "{contentTitle}" in the space {spaceName}.', array_merge([
                'spaceName' =>  Html::encode($this->source->content->container->displayName),
            ]));
        }

        return Yii::t('ContentModule.notifications_views_ContentCreated', '{displayName} reopened the event "{contentTitle}".', $params);
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject()
    {
        return Yii::t('CalendarModule.notification', '{displayName} reopened the event "{contentTitle}".', [
            'displayName' => Html::encode($this->originator->displayName),
            'contentTitle' => $this->getContentInfo($this->source, false),
        ]);
    }
}
