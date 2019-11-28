<?php


namespace humhub\modules\calendar\interfaces;



use Yii;
use humhub\modules\calendar\models\reminder\ReminderProcessor;
use yii\base\Component;

class ReminderService extends Component
{
    /**
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     */
    public function sendAllReminder() {
        $calendarService = Yii::$app->getModule('calendar')->get(CalendarService::class);
        (new ReminderProcessor(['calendarService' => $calendarService]))->run();
    }
}