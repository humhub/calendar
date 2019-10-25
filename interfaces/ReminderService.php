<?php


namespace humhub\modules\calendar\interfaces;


use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\calendar\models\CalendarReminder;
use humhub\modules\calendar\notifications\Remind;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\components\ActiveQueryUser;
use humhub\modules\user\models\User;
use Yii;
use yii\base\Component;

class ReminderService extends Component
{
    public function sendAllReminder() {
        /* @var $calendarService CalendarService */
        $calendarService = Yii::$app->getModule('calendar')->get(CalendarService::class);
        foreach ($calendarService->getUpcomingEntries(null, null, null, [CalendarEntryQuery::FILTER_INCLUDE_NONREADABLE]) as $entry) {
            // We currently only support calendar entries
            if(!$entry instanceof  CalendarEntry) {
                continue;
            }


            $recepients = null;

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
        $skipUsers = [];

        // Note: User level reminder will always come before space level reminder
        foreach (CalendarReminder::getByModel($entry) as $reminder) {

            if($reminder->isUserLevelReminder()) {
                // We mark that there is an existing user level reminder, in order to exclude them for default
                $skipUsers[] = $reminder->contentcontainer_id;
            }

            if(!$reminder->checkMaturity($entry)) {
                continue;
            }

            if($reminder->isUserLevelReminder()) { // User overwritten
                $this->sendReminder($entry, User::find()->where(['user.contentcontainer_id' => $reminder->contentcontainer_id]));
            } else {
                $this->sendReminder($entry, $this->getRecepientQuery($entry, $skipUsers));

                // If we are here, we've handled all user level and container level reminder so we can skip other reminder
                // TODO: make sure we invalidate other reminder which may match
                return true;
            }
        }

        return $skipUsers;
    }

    /**
     * @param CalendarEntry $entry
     * @param array $skipUsers
     * @return array|ActiveQueryUser|\yii\db\ActiveQuery
     */
    private function getRecepientQuery(CalendarEntry $entry, $skipUsers = [])
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
     * @param CalendarEntry $entry
     * @param $skipUsers
     * @throws \yii\base\InvalidConfigException
     */
    private function handleDefaultReminder(CalendarEntry $entry, $skipUsers = [])
    {
        foreach (CalendarReminder::getDefaults($entry->content->container, true) as $reminder) {
            if($reminder->checkMaturity($entry)) {
                $this->sendReminder($entry, $this->getRecepientQuery($entry, $skipUsers));
            }
        }
    }

    /**
     * @param CalendarEntry $entry
     * @param ActiveQueryUser|User[] $recipients
     * @throws \yii\base\InvalidConfigException
     */
    private function sendReminder(CalendarEntry $entry, $recipients)
    {
        // TODO: Clear old reminder notifications
        Remind::instance()->from($entry->content->createdBy)->about($entry)->sendBulk($recipients);
    }




}