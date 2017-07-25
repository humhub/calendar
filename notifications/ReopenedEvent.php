<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace  humhub\modules\calendar\notifications;

use humhub\libs\Html;
use humhub\modules\content\notifications\ContentCreatedNotificationCategory;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\space\models\Space;
use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 21.07.2017
 * Time: 23:12
 */
class ReopenedEvent extends BaseNotification
{
    /**
     * @inheritdoc
     */
    public $moduleId = 'calendar';

    /**
     * @inheritdoc
     */
    public $viewName = 'calendarNotification';

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
        if($this->source->content->container instanceof Space) {
            return Yii::t('CalendarModule.notifications_views_CanceledEvent', '{displayName} reopened event {contentTitle} in space {spaceName}.', [
                'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
                'contentTitle' => $this->getContentInfo($this->source, false),
                'spaceName' =>  Html::encode($this->source->content->container->displayName)
            ]);
        } else {
            return Yii::t('ContentModule.notifications_views_ContentCreated', '{displayName} reopened event {contentTitle}.', [
                'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
                'contentTitle' => $this->getContentInfo($this->source, false)
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject()
    {
        return Yii::t('CalendarModule.notifications_views_CanceledEvent', '{displayName} reopened event {contentTitle}.', [
            'displayName' => Html::tag('strong', Html::encode($this->originator->displayName)),
            'contentTitle' => $this->getContentInfo($this->source, false)
        ]);
    }
}