<?php

namespace humhub\modules\calendar\controllers;

use humhub\modules\calendar\models\forms\InviteForm;
use humhub\modules\calendar\widgets\ParticipantItem;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\stream\actions\Stream;
use humhub\modules\user\models\User;
use humhub\modules\user\widgets\UserListBox;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\calendar\permissions\CreateEntry;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\widgets\ModalClose;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\RangeNotSatisfiableHttpException;
use yii\web\Response;

/**
 * EntryController used to display, edit or delete calendar entries
 *
 * @package humhub.modules_core.calendar.controllers
 * @author luke
 */
class EntryController extends ContentContainerController
{

    /**
     * @inheritdoc
     */
    public $hideSidebar = true;

    /**
     * @param $id
     * @param null $cal
     * @return string
     * @throws HttpException
     * @throws Throwable
     * @throws Exception
     */
    public function actionView($id, $cal = null)
    {
        $entry = $this->getCalendarEntry($id);

        if (!$entry) {
            throw new HttpException('404');
        }

        return $this->renderEntry($entry, $cal);
    }

    public function renderEntry(CalendarEntry $entry, $cal = null)
    {
        // We need the $cal information, since the edit redirect in case of fullcalendar view is other than stream view
        if ($cal) {
            return $this->renderModal($entry, $cal);
        }

        return $this->render('view', ['entry' => $entry, 'stream' => true]);
    }

    /**
     * @param $parent_id
     * @param $recurrence_id
     * @param null $cal
     * @return mixed
     * @throws Throwable
     * @throws Exception
     */
    public function actionViewRecurrence($parent_id, $recurrence_id, $cal = null)
    {
        $recurrenceRoot = $this->getCalendarEntry($parent_id);

        if(!$recurrenceRoot) {
            throw new NotFoundHttpException();
        }

        $recurrence = $recurrenceRoot->getRecurrenceQuery()->getRecurrenceInstance($recurrence_id);

        if(!$recurrence) {
            $recurrence = $recurrenceRoot->getRecurrenceQuery()->expandSingle($recurrence_id);
        }

        if(!$recurrence) {
            throw new NotFoundHttpException();
        }

        return $this->renderEntry($recurrence, $cal);
    }

    /**
     * @param $entry
     * @param $cal
     * @return string
     */
    protected function renderModal($entry, $cal)
    {
        return $this->renderAjax('modal', [
            'entry' => $entry,
            'editUrl' => Url::toEditEntry($entry, $cal, $this->contentContainer),
            'canManageEntries' => $entry->content->canEdit(),
            'contentContainer' => $this->contentContainer,
        ]);
    }

    /**
     * @param $id
     * @param $type
     * @return Response
     * @throws HttpException
     * @throws Throwable
     * @throws Exception
     */
    public function actionRespond($id, $type)
    {
        $calendarEntry = $this->getCalendarEntry($id);

        if (!$calendarEntry) {
            throw new HttpException('404');
        }

        if(!$calendarEntry->canRespond(Yii::$app->user->identity)) {
            throw new HttpException(403);
        }

        $calendarEntry->setParticipationStatus(Yii::$app->user->identity, (int) $type);

        return $this->asJson(['success' => true]);
    }

    /**
     *
     * @param null $id calendar entry id
     * @param null $start FullCalendar start datetime e.g.: 2020-01-01 00:00:00
     * @param null $end FullCalendar end datetime e.g.: 2020-01-02 00:00:00
     * @param null $cal whether or not the edit event came from the calendar view
     * @return string
     * @throws HttpException
     * @throws Throwable
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function actionEdit($id = null, $start = null, $end = null, $cal = null)
    {
        if(empty($id) && !$this->canCreateEntries()) {
            throw new HttpException(403);
        }

        if (empty($id) && $this->canCreateEntries()) {
            $calendarEntryForm = CalendarEntryForm::createEntry($this->contentContainer, $start, $end);
        } else {
            $calendarEntryForm = new CalendarEntryForm(['entry' => $this->getCalendarEntry($id)]);
            if(!$calendarEntryForm->entry->content->canEdit()) {
                throw new HttpException(403);
            }
        }

        if (!$calendarEntryForm->entry) {
            throw new HttpException(404);
        }

        if ($calendarEntryForm->load(Yii::$app->request->post()) && $calendarEntryForm->save()) {
            if(empty($cal)) {
                return ModalClose::widget(['saved' => true]);
            }

            return $this->renderModal($calendarEntryForm->entry, 1);
        }

        if ($calendarEntryForm->isAllDay()) {
            $calendarEntryForm->setDefaultTime();
        }

        return $this->renderAjax('edit', [
            'calendarEntryForm' => $calendarEntryForm,
            'contentContainer' => $this->contentContainer,
            'editUrl' => Url::toEditEntry($calendarEntryForm->entry, $cal, $this->contentContainer)
        ]);
    }

    /**
     * Action for participants pagination of the Calendar entry
     *
     * @param null $id
     * @return string
     * @throws Exception
     * @throws HttpException
     * @throws InvalidConfigException
     * @throws Throwable
     * @throws \yii\db\IntegrityException
     */
    public function actionParticipants($id = null)
    {
        $calendarEntryForm = new CalendarEntryForm(['entry' => $this->getCalendarEntry($id)]);
        if (!$calendarEntryForm->entry->content->canEdit()) {
            throw new HttpException(403);
        }

        return $this->renderAjax('edit-participants', [
            'calendarEntryForm' => $calendarEntryForm,
        ]);
    }

    public function actionAddParticipantsForm()
    {
        return $this->renderAjax('edit-participants-add', [
            'statuses' => ParticipantItem::getStatuses(),
        ]);
    }

    public function actionAddParticipants()
    {
        $this->forcePostRequest();

        $entryId = Yii::$app->request->post('entryId');
        $status = Yii::$app->request->post('status');
        $guids = Yii::$app->request->post('guids');

        if (empty($guids)) {
            return $this->asJson([
                'error' => Yii::t('CalendarModule.base', 'Please select new participants.'),
            ]);
        }

        if (!ParticipantItem::hasStatus($status)) {
            throw new HttpException(403, 'Wrong status!');
        }

        $entry = $this->getCalendarEntry($entryId);
        if (!$entry->content->canEdit()) {
            throw new HttpException(403);
        }

        $users = User::find()->where(['IN', 'guid', $guids])->all();
        $addedUsers = [];
        $newParticipantsHtml = [];
        foreach ($users as $user) {
            if ($entry->participation->findParticipant($user)) {
                continue;
            }
            $entry->participation->setParticipationStatus($user, $status);
            $addedUsers[] = $user->displayName;
            $newParticipantsHtml[] = ParticipantItem::widget([
                'entry' => $entry,
                'user' => $user,
            ]);
        }

        if (empty($addedUsers)) {
            return $this->asJson([
                'warning' => Yii::t('CalendarModule.base', 'No new participants were added.'),
            ]);
        }

        return $this->asJson([
            'success' => Yii::t('CalendarModule.base', 'Added: {users}', [
                'users' => implode(', ', $addedUsers)
            ]),
            'html' => $newParticipantsHtml,
        ]);
    }

    public function actionUpdateParticipantStatus()
    {
        $this->forcePostRequest();

        $entryId = Yii::$app->request->post('entryId');
        $userId = Yii::$app->request->post('userId');
        $status = Yii::$app->request->post('status');

        if (!ParticipantItem::hasStatus($status)) {
            throw new HttpException(403, 'Wrong status!');
        }

        $entry = $this->getCalendarEntry($entryId);
        if (!$entry->content->canEdit()) {
            throw new HttpException(403);
        }

        $user = User::findOne($userId);
        if (!$user) {
            throw new HttpException(404, 'User not found!');
        }

        $entry->participation->setParticipationStatus($user, $status);

        return $this->asJson([
            'success' => true,
            'message' => Yii::t('CalendarModule.base', 'Status updated.'),
        ]);
    }

    public function actionRemoveParticipant()
    {
        $this->forcePostRequest();

        $entryId = Yii::$app->request->post('entryId');
        $userId = Yii::$app->request->post('userId');

        $entry = $this->getCalendarEntry($entryId);
        if (!$entry->content->canEdit()) {
            throw new HttpException(403);
        }

        $user = User::findOne($userId);
        if (!$user) {
            throw new HttpException(404, 'User not found!');
        }

        $participant = $entry->participation->findParticipant($user);

        if (!$participant || !$participant->delete()) {
            return $this->asJson([
                'success' => false,
                'message' => Yii::t('CalendarModule.base', 'Cannot remove the participant!'),
            ]);
        }

        return $this->asJson([
            'success' => true,
            'message' => Yii::t('CalendarModule.base', 'Participant removed.'),
        ]);
    }

    /**
     * @param $id
     * @return Response
     * @throws HttpException
     * @throws Throwable
     * @throws Exception
     */
    public function actionToggleClose($id)
    {
        $entry = $this->getCalendarEntry($id);

        if(!$entry) {
            throw new HttpException(404);
        }

        if(!$entry->content->canEdit()) {
            throw new HttpException(403);
        }

        $entry->toggleClosed();

        return $this->asJson(Stream::getContentResultEntry($entry->content));
    }

    /**
     * @return mixed
     * @throws HttpException
     * @throws Throwable
     * @throws Exception
     */
    public function actionUserList()
    {
        $calendarEntry = $this->getCalendarEntry(Yii::$app->request->get('id'));

        if ($calendarEntry == null) {
            throw new HttpException('404', Yii::t('CalendarModule.base', "Event not found!"));
        }
        $state = Yii::$app->request->get('state');

        $query = User::find();
        $query->leftJoin('calendar_entry_participant', 'user.id=calendar_entry_participant.user_id AND calendar_entry_participant.calendar_entry_id=:calendar_entry_id AND calendar_entry_participant.participation_state=:state', [
            ':calendar_entry_id' => $calendarEntry->id,
            ':state' => $state
        ]);
        $query->where('calendar_entry_participant.id IS NOT NULL');

        $title = "";
        if ($state == CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED) {
            $title = Yii::t('CalendarModule.base', 'Attending users');
        } elseif ($state == CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED) {
            $title = Yii::t('CalendarModule.base', 'Declining users');
        } elseif ($state == CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE) {
            $title = Yii::t('CalendarModule.base', 'Maybe attending users');
        }
        return $this->renderAjaxContent(UserListBox::widget(['query' => $query, 'title' => $title]));
    }

    /**
     * @param $id
     * @return EntryController|\yii\console\Response|Response
     * @throws HttpException
     * @throws Throwable
     * @throws Exception
     */
    public function actionDelete($id)
    {
        $this->forcePostRequest();

        $calendarEntry = $this->getCalendarEntry($id);

        if (!$calendarEntry) {
            throw new HttpException('404', Yii::t('CalendarModule.base', "Event not found!"));
        }

        if (!$calendarEntry->content->canEdit()) {
            throw new HttpException('403', Yii::t('CalendarModule.base', "You don't have permission to delete this event!"));
        }

        $calendarEntry->delete();

        return Yii::$app->request->isAjax
            ? $this->asJson(['success' => true])
            : $this->redirect(Url::toCalendar($this->contentContainer));
    }

    /**
     * Returns a readable calendar entry by given id
     *
     * @param int $id
     * @return CalendarEntry
     * @throws Throwable
     * @throws Exception
     */
    protected function getCalendarEntry($id)
    {
        if(!$id) {
            throw new HttpException(404);
        }

        $entry = CalendarEntry::find()->contentContainer($this->contentContainer)->readable()->where(['calendar_entry.id' => $id])->one();

        if(!$entry) {
            throw new HttpException(404);
        }

        return $entry;
    }

    /**
     * Checks the CreatEntry permission for the given user on the given contentContainer.
     * @return bool
     * @throws InvalidConfigException
     */
    private function canCreateEntries()
    {
        return $this->contentContainer->permissionManager->can(CreateEntry::class);
    }

    /**
     * @return Response
     * @throws Throwable
     * @throws Exception
     * @throws RangeNotSatisfiableHttpException
     */
    public function actionGenerateics($id)
    {
        $calendarEntry = $this->getCalendarEntry($id);
        $ics = $calendarEntry->generateIcs();
        return Yii::$app->response->sendContentAsFile($ics, uniqid() . '.ics', ['mimeType' => 'text/calendar']);
    }

    /**
     * Display a form to invite new participants
     *
     * @param $id
     * @return string
     */
    public function actionInvite($id)
    {
        $inviteForm = new InviteForm(['entryId' => $id]);

        if ($inviteForm->load(Yii::$app->request->post()) && $inviteForm->save()) {
            if (empty($inviteForm->invitedUsers)) {
                return ModalClose::widget(['warn' => Yii::t('CalendarModule.base', 'No new users have been invited.')]);
            }

            $invitedUsers = [];
            foreach ($inviteForm->invitedUsers as $invitedUser) {
                $invitedUsers[] = '"' . $invitedUser->displayName . '"';
            }
            return ModalClose::widget(['success' => Yii::t('CalendarModule.base', 'Invited: {users}', [
                'users' => implode(', ', $invitedUsers)
            ])]);
        }

        return $this->renderAjax('invite', ['inviteForm' => $inviteForm]);
    }
}
