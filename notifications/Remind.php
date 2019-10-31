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
class Remind extends BaseNotification
{
    /**
     * @inheritdoc
     */
    public $moduleId = 'calendar';

    /**
     * @inheritdoc
     */
    public $viewName = 'remindNotification';

    /**
     * @var bool
     */
    public $suppressSendToOriginator = false;

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
        return '<b>Reminder!!</b>';
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject()
    {
        return Yii::t('CalendarModule.notifications_views_CanceledEvent', 'Reminder', []);
    }
}