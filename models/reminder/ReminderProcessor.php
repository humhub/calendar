<?php


namespace humhub\modules\calendar\models\reminder;


use Exception;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\CalendarEventReminderIF;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\calendar\notifications\Remind;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\models\User;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\db\IntegrityException;

class ReminderProcessor extends Model
{
    /**
     * @var CalendarService
     */
    public $calendarService;

    public $handledReminders = [];

    /**
     * @throws InvalidConfigException
     * @throws \Throwable
     */
    public function run()
    {
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
         */
        if(empty(CalendarReminder::getDefaults())) {
            foreach (CalendarReminder::getContainerWithDefaultReminder() as $contentContainer) {
                $this->runByUpcomingEvents($contentContainer->getPolymorphicRelation());
            }

            $this->runEntryLevelOnly();
        } else {
            $this->runByUpcomingEvents();
        }
    }

    /**
     * @param ContentContainerActiveRecord|null $container
     * @throws InvalidConfigException
     * @throws \Throwable
     */
    private function runByUpcomingEvents(ContentContainerActiveRecord $container = null)
    {
        foreach ($this->calendarService->getUpcomingEntries($container, null, null, [CalendarEntryQuery::FILTER_INCLUDE_NONREADABLE]) as $entry) {

            if(!$entry instanceof CalendarEventReminderIF) {
                continue;
            }

            $skipUsers = $this->handleEntryLevelReminder($entry);

            if($skipUsers === true) { // Handled all recepients already
                continue;
            }

            $this->handleDefaultReminder($entry, $skipUsers);
        }
    }

    /**
     * @throws IntegrityException
     * @throws Exception
     */
    private function runEntryLevelOnly()
    {
        $entryLevelReminder = CalendarReminder::findEntryLevelReminder()->andWhere(['NOT IN', 'calendar_reminder.id', $this->handledReminders]) ->all();

        $entryHandled = [];
        foreach ($entryLevelReminder as $reminder) {
            $entry = $reminder->getEntry();
            $entryKey = get_class($entry).':'.$entry->id;
            if(!isset($entryHandled[$entryKey])) {
                $this->handleEntryLevelReminder($reminder->getEntry());
                $entryHandled[$entryKey] = true;
            }
        }
    }

    /**
     * This function handles all entry level reminders for a given CalendarEntry.
     *
     * This function will return
     *
     *  - true in case there was an container wide default reminder for this entry
     *  - an array of contentcontainer ids of users already handled in case there was no container wide default for this entry
     *
     * @param CalendarEventReminderIF $entry
     * @return array|bool
     * @throws Exception
     */
    private function handleEntryLevelReminder(CalendarEventReminderIF $entry)
    {
        // We keep track of users which have an entry level reminder set for this entry
        $skipUsers = [];

        // We keep track of reminder blocks already sent for a container (or global)
        $sentContainer = [];

        // Note: User level reminder are sorted before container level reminder (see query order_by)
        foreach (CalendarReminder::getEntryLevelReminder($entry) as $reminder) {
            if($reminder->isUserLevelReminder()) {
                $skipUsers[] = $reminder->contentcontainer_id;
            }

            // Skip reminder which do not match yet
            if(!$reminder->checkMaturity($entry)) {
                continue;
            }

            // Check if reminder has already been sent
            if(!$reminder->isActive($entry)) {
                $sentContainer[$reminder->contentcontainer_id] = true;
                continue;
            }

            // Make sure no other reminder which is closer to the event has already been sent (see query order_by)
            if(isset($sentContainer[$reminder->contentcontainer_id])) {
                // If yes, invalidate the reminder
                $reminder->acknowledge($entry);
                continue;
            }

            if($this->sendEntryLevelReminder($reminder, $entry, $skipUsers)) {
                $sentContainer[$reminder->contentcontainer_id] = true;
            }
        }

        return isset($sentContainer[null]) ? true : $skipUsers;
    }

    /**
     * @param CalendarReminder $reminder
     * @param CalendarEventReminderIF $entry
     * @param array $skipUsers
     * @return bool
     * @throws InvalidConfigException
     * @throws IntegrityException
     */
    public function sendEntryLevelReminder(CalendarReminder $reminder, CalendarEventReminderIF $entry = null, $skipUsers = [])
    {
        if(!$entry) {
            $entry = $reminder->getEntry();
        }

        if(!$entry) {
            return false;
        }

        if($reminder->isUserLevelReminder()) {
            $this->sendReminder($reminder, $entry, User::find()->where(['user.contentcontainer_id' => $reminder->contentcontainer_id]));
        } else {
            $this->sendReminder($reminder, $entry, $this->getRecipientQuery($entry, $skipUsers));
        }

        return true;
    }

    /**
     * @param CalendarEventReminderIF $entry
     * @param array $skipUsers
     * @return array|ActiveQueryUser|ActiveQuery
     */
    protected function getRecipientQuery(CalendarEventReminderIF $entry, $skipUsers = [])
    {
        $query = $entry->findUsersByInterest();

        if(!empty($skipUsers)) {
            $query->andWhere(['NOT IN', 'user.contentcontainer_id', $skipUsers]);
        }

        return $query;
    }

    /**
     * @param CalendarEventReminderIF $entry
     * @param $skipUsers
     * @throws InvalidConfigException
     */
    private function handleDefaultReminder(CalendarEventReminderIF $entry, $skipUsers = [])
    {
        $sent = false;
        foreach (CalendarReminder::getDefaults($entry->getContentRecord()->container, true) as $reminder) {
            if(!$reminder->checkMaturity($entry)) {
                continue;
            }

            if(!$reminder->isActive($entry)) {
                $sent = true;
                continue;
            }

            if(!$sent) {
                $sent = $this->sendReminder($reminder, $entry, $this->getRecipientQuery($entry, $skipUsers));
            } else {
                // Another reminder closer to the event start was already sent
                $reminder->acknowledge($entry);
            }
        }
    }

    /**
     * @param CalendarReminder $reminder
     * @param CalendarEventReminderIF $entry
     * @return bool
     * @throws Exception
     */
    public function isReadyToSent(CalendarReminder $reminder, CalendarEventReminderIF $entry)
    {
        $this->handledReminders[] = $reminder->id;
        return $reminder->checkMaturity($entry) && $reminder->isActive($entry);
    }


    /**
     * @param CalendarReminder $reminder
     * @param CalendarEventReminderIF $entry
     * @param ActiveQueryUser|User[] $recipients
     * @return bool
     * @throws InvalidConfigException
     */
    private function sendReminder(CalendarReminder $reminder, CalendarEventReminderIF $entry, $recipients)
    {
        // TODO: Clear old reminder notifications
        Remind::instance()->from($entry->getContentRecord()->createdBy)->about($entry)->sendBulk($recipients);
        $reminder->acknowledge($entry);
        return true;
    }
}