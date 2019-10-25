<?php


namespace humhub\modules\calendar\interfaces;



use Yii;
use humhub\modules\calendar\models\CalendarReminder;
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
        $processor = new ReminderProcessor(['calendarService' => $calendarService]);

        /**
         * We differ the following cases for optimization reasons.
         *
         * If global and container default reminders are given:
         *
         *  - Loop through all upcoming events
         *  - Handle entry level reminder
         *  - Handle remaining default reminder
         *
         * If no global default reminders are given:
         *
         *  - Loop through containers with given default settings
         *  - Handle entry level reminder
         *  - Handle container default reminder
         *
         *  => Skips upcoming entries without reminders
         *
         * If no global default and no container default reminders are given, we simply loop through the entry level reminder.
         *
         *  - Loop through and handle all entry level reminder
         *
         */

        if(empty(CalendarReminder::getDefaults())) {
            foreach (CalendarReminder::getContainerWithDefaultReminder() as $contentContainer) {
                $processor->run($contentContainer->getPolymorphicRelation());
            }

            foreach (CalendarReminder::findEntryLevelReminder()->andWhere(['NOT IN', 'calendar_reminder.id', $processor->handledReminders]) as $reminder) {
                $processor->sendEntryLevelReminder($reminder);
            }
        } else {
            $processor->run();
        }
    }






}