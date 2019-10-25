<?php


namespace humhub\modules\calendar\models;


use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\notifications\Remind;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\models\User;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\ActiveQuery;

class ReminderProcessor extends Model
{
    /**
     * @var CalendarService
     */
    public $calendarService;

    public $handledReminders = [];

    /**
     * @param ContentContainerActiveRecord|null $container
     * @throws InvalidConfigException
     * @throws \Throwable
     */
    public function run(ContentContainerActiveRecord $container = null)
    {
        foreach ($this->calendarService->getUpcomingEntries($container, null, null, [CalendarEntryQuery::FILTER_INCLUDE_NONREADABLE]) as $entry) {
            // We currently only support calendar entries
            if(!$entry instanceof  CalendarEntry) {
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
     * @param CalendarEntry $entry
     * @return array|bool
     * @throws \Exception
     */
    private function handleEntryLevelReminder(CalendarEntry $entry)
    {
        // We keep track of users already handled by entry level reminder
        $skipUsers = [];

        // Note: User level reminder are sorted before container level reminder
        foreach (CalendarReminder::getByEntry($entry) as $reminder) {
            if($reminder->isUserLevelReminder()) {
                // We mark that there is an existing user level reminder, in order to exclude them from default reminders
                $skipUsers[] = $reminder->contentcontainer_id;
            }

            if(!$this->isReadyToSent($reminder, $entry)) {
                continue;
            }

            $this->sendEntryLevelReminder($reminder, $entry);

            if(!$reminder->isUserLevelReminder()) {
                // This is a container wide entry level reminder, which means we have handled all reminder for this container
                // TODO: make sure we invalidate other reminder which may match
                return true;
            }


        }

        return $skipUsers;
    }

    /**
     * @param CalendarReminder $reminder
     * @param CalendarEntry $entry
     * @throws InvalidConfigException
     */
    public function sendEntryLevelReminder(CalendarReminder $reminder, CalendarEntry $entry = null)
    {
        if(!$entry) {
            $entry = $reminder->getPolymorphicRelation();
        }

        if(!$entry) {
            return;
        }

        if($reminder->isUserLevelReminder()) {
            $this->sendReminder($reminder, $entry, User::find()->where(['user.contentcontainer_id' => $reminder->contentcontainer_id]));
        } else {
            $this->sendReminder($reminder, $entry, $this->getRecepientQuery($entry));
        }
    }

    /**
     * @param CalendarEntry $entry
     * @param array $skipUsers
     * @return array|ActiveQueryUser|ActiveQuery
     */
    protected function getRecepientQuery(CalendarEntry $entry, $skipUsers = [])
    {
        $query = [];
        if($entry->content->container instanceof Space) { // Space level overwritten
            $query = Membership::getSpaceMembersQuery($entry->content->container);
            // TODO: Exclude non participating users
            //->andWhere(['NOT IN', 'user.id', $entry->findParticipantUsersByState(CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED)->select('user.id')]);
        } else if($entry->content->container instanceof  User) {
            return $entry->findParticipantUsersByState([
                CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED,
                CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE]);
        }


        if(!empty($skipUsers)) {
            $query->andWhere(['NOT IN', 'user.contentcontainer_id', $skipUsers]);
        }

        return $query;
    }

    /**
     * @param CalendarReminder $reminder
     * @param CalendarEntry $entry
     * @return bool
     * @throws \Exception
     */
    public function isReadyToSent(CalendarReminder $reminder, CalendarEntry $entry)
    {
        $this->handledReminders[] = $reminder->id;
        return $reminder->checkMaturity($entry) && $reminder->isActive($entry);
    }



    /**
     * @param CalendarEntry $entry
     * @param $skipUsers
     * @throws InvalidConfigException
     */
    private function handleDefaultReminder(CalendarEntry $entry, $skipUsers = [])
    {
        foreach (CalendarReminder::getDefaults($entry->content->container, true) as $reminder) {
            if($this->isReadyToSent($reminder, $entry)) {
                $this->sendReminder($reminder, $entry, $this->getRecepientQuery($entry, $skipUsers));
            }
        }
    }

    /**
     * @param CalendarReminder $reminder
     * @param CalendarEntry $entry
     * @param ActiveQueryUser|User[] $recipients
     * @return bool
     * @throws InvalidConfigException
     */
    private function sendReminder(CalendarReminder $reminder, CalendarEntry $entry, $recipients)
    {
        // TODO: Clear old reminder notifications
        Remind::instance()->from($entry->content->createdBy)->about($entry)->sendBulk($recipients);
        $reminder->acknowledge($entry);
        return true;
    }
}