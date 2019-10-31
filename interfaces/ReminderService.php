<?php


namespace humhub\modules\calendar\interfaces;



use Yii;
use humhub\modules\calendar\models\ReminderProcessor;
use yii\base\Component;

class ReminderService extends Component
{
    /**
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function sendAllReminder() {
        $calendarService = Yii::$app->getModule('calendar')->get(CalendarService::class);
        (new ReminderProcessor(['calendarService' => $calendarService]))->run();
    }






}