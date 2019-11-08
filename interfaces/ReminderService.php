<?php


namespace humhub\modules\calendar\interfaces;



use Yii;
use humhub\modules\calendar\models\reminder\ReminderProcessor;
use yii\base\Component;

class ReminderService extends Component
{
    public function sendAllReminder() {
        try {
            $calendarService = Yii::$app->getModule('calendar')->get(CalendarService::class);
            (new ReminderProcessor(['calendarService' => $calendarService]))->run();
        } catch (\Exception $e) {
            Yii::error($e);
        } catch (\Throwable $e) {
            Yii::error($e);
        }
    }
}