<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace  humhub\modules\calendar\notifications;

use humhub\libs\Html;
use humhub\modules\calendar\interfaces\reminder\CalendarEventReminderIF;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\notification\components\BaseNotification;
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
        /* @var $record CalendarEventReminderIF */
        if($this->source instanceof CalendarEventReminderIF) {
            return Yii::t('CalendarModule.reminder', 'You have an <strong>{type}</strong> coming up: {title}', [
                'type' => Html::encode($this->getEventType()),
                'title' => RichText::preview($this->source->getTitle(), 25)
            ]);
        }

        return Yii::t('CalendarModule.reminder', 'You have an <strong>{type}</strong> coming up', ['type' => $this->getEventType()]);
    }

    public function getEventType()
    {
        if($this->source instanceof CalendarEventReminderIF) {
            $type = $this->source->getEventType();
            if($type) {
                return $type->getTitle();
            }
        }

        return Yii::t('CalendarModule.base', 'Event');
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject()
    {
        if($this->source instanceof CalendarEventReminderIF) {
            return Yii::t('CalendarModule.reminder', 'Upcoming {type}: {title}', [
                'type' => $this->getEventType(),
                'title' => $this->source->getTitle()
            ]);
        }

        return Yii::t('CalendarModule.reminder', 'Upcoming {type}', ['type' => $this->getEventType()]);
    }
}