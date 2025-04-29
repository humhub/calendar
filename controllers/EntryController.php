<?php

namespace humhub\modules\calendar\controllers;

use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\calendar\notifications\MarkAttend;
use humhub\modules\calendar\notifications\ParticipantAdded;
use humhub\modules\calendar\widgets\ParticipantItem;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\models\Content;
use humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter;
use humhub\modules\stream\actions\Stream;
use humhub\modules\stream\actions\StreamEntryResponse;
use humhub\modules\user\models\User;
use humhub\modules\user\models\UserPicker;
use humhub\widgets\ModalClose;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
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

        if (!$entry->content->canView()) {
            throw new ForbiddenHttpException('You have no permission to view the event!');
        }

        $this->view->meta->setDescription(RichTextToPlainTextConverter::process($entry->getTitle() . ' - ' . $entry->getDescription()));

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

        if (!$recurrenceRoot) {
            throw new NotFoundHttpException();
        }

        $recurrence = $recurrenceRoot->getRecurrenceQuery()->getRecurrenceInstance($recurrence_id);

        if (!$recurrence) {
            $recurrence = $recurrenceRoot->getRecurrenceQuery()->expandSingle($recurrence_id);
        }

        if (!$recurrence) {
            throw new NotFoundHttpException();
        }

        return $this->renderEntry($recurrence, $cal);
    }

    /**
     * @param CalendarEntry $entry
     * @param $cal
     * @return string
     */
    protected function renderModal(CalendarEntry $entry, $cal)
    {
        return $this->renderAjax('modal', [
            'entry' => $entry,
            'editUrl' => $entry->content->canEdit() ? Url::toEditEntry($entry, $cal, $this->contentContainer) : false,
            'inviteUrl' => $entry->canInvite() ? Url::toParticipationUserList($entry, CalendarEntryParticipant::PARTICIPATION_STATE_INVITED) : false,
            'contentContainer' => $this->contentContainer,
        ]);
    }

    /**
     * Render modal window with participation settings form and participants manager
     *
     * @param CalendarEntry $entry
     * @param string|null $activeTab by default 'settings' or null, 'list'
     * @param bool $isNewRecord
     * @return string
     */
    protected function renderModalParticipation(CalendarEntry $entry, ?string $activeTab = null, bool $isNewRecord = false): string
    {
        if ($activeTab === 'list' && $entry->participation_mode == CalendarEntryParticipation::PARTICIPATION_MODE_NONE) {
            $activeTab = null;
        }

        return $this->renderAjax('modal-participants', [
            'calendarEntryParticipationForm' => new CalendarEntryParticipationForm(['entry' => $entry]),
            'activeTab' => $activeTab,
            'editUrl' => Url::toEditEntry($entry),
            'saveUrl' => Url::toEditEntryParticipation($entry),
            'isNewRecord' => $isNewRecord,
            'widgetOptions' => [
                'id' => 'calendar-entry-participation-form',
                'data' => [
                    'ui-widget' => 'calendar.participation.Form',
                    'ui-init' => true,
                    'entry-id' => $entry->id,
                    'update-url' => $this->contentContainer->createUrl('/calendar/entry/update-participant-status'),
                    'remove-url' => $this->contentContainer->createUrl('/calendar/entry/remove-participant'),
                    'filter-url' => $this->contentContainer->createUrl('/calendar/entry/participants-list'),
                ],
            ],
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

        if (!$calendarEntry->canRespond(Yii::$app->user->identity)) {
            throw new ForbiddenHttpException('You cannot be a participant of the event!');
        }

        if ($calendarEntry->isPast()) {
            throw new ForbiddenHttpException('Event is over!');
        }

        if (!$calendarEntry->setParticipationStatus(Yii::$app->user->identity, (int)$type)) {
            throw new ForbiddenHttpException('You cannot be a participant of the event!');
        }

        if ($type == CalendarEntryParticipation::PARTICIPATION_STATUS_ACCEPTED) {
            MarkAttend::instance()->from(Yii::$app->user->identity)
                ->about($calendarEntry)
                ->sendBulk([Yii::$app->user->identity]);
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Add a calendar Entry from wall stream
     *
     * @return string
     * @throws Exception
     * @throws HttpException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function actionAddFromWall()
    {
        $date = (new DateTime('now'))->setTime(date('H'), 0)->format(CalendarUtils::DATE_FORMAT_ATOM);
        return $this->actionEdit(null, $date, $date, 1, 'month', true);
    }

    /**
     *
     * @param null $id calendar entry id
     * @param null $start FullCalendar start datetime e.g.: 2020-01-01 00:00:00
     * @param null $end FullCalendar end datetime e.g.: 2020-01-02 00:00:00
     * @param null $cal whether or not the edit event came from the calendar view
     * @param string|null $view FullCalendar view mode, 'month'
     * @param bool $wall True when a Calendary Entry is created/updated from wall stream
     * @return string|Response
     * @throws HttpException
     * @throws Throwable
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function actionEdit($id = null, $start = null, $end = null, $cal = null, $view = null, $wall = null)
    {
        if (empty($id) && !$this->canCreateEntries()) {
            throw new HttpException(403);
        }

        if (empty($id) && $this->canCreateEntries()) {
            $calendarEntryForm = CalendarEntryForm::createEntry($this->contentContainer, $start, $end, $view, $wall);
        } else {
            $calendarEntryForm = new CalendarEntryForm(['entry' => $this->getCalendarEntry($id)]);
            if (!$calendarEntryForm->entry->content->canEdit()) {
                throw new HttpException(403);
            }
        }

        if (!$calendarEntryForm->entry) {
            throw new HttpException(404);
        }

        if ($calendarEntryForm->load(Yii::$app->request->post()) && $calendarEntryForm->save()) {
            if ($wall) {
                $entry = StreamEntryResponse::getAsArray($calendarEntryForm->entry->content);
                $entry['reloadWall'] = true;
                $entry['content'] = $entry['output'];
                $entry['output'] = $this->renderModalParticipation($calendarEntryForm->entry, null, true);

                return $this->asJson($entry);
            }

            if (empty($cal)) {
                return ModalClose::widget(['saved' => true]);
            }

            return empty($id)
                ? $this->renderModalParticipation($calendarEntryForm->entry, null, true)
                : $this->renderModal($calendarEntryForm->entry, 1);
        }

        if ($calendarEntryForm->isAllDay()) {
            $calendarEntryForm->setDefaultTime();
        }

        return $this->renderAjax('edit', [
            'calendarEntryForm' => $calendarEntryForm,
            'contentContainer' => $this->contentContainer,
            'editUrl' => Url::toEditEntry($calendarEntryForm->entry, $cal, $this->contentContainer, $calendarEntryForm->wall),
        ]);
    }

    public function actionEditParticipation($id = null)
    {
        if (empty($id)) {
            throw new HttpException(403);
        }

        $calendarEntryParticipationForm = new CalendarEntryParticipationForm(['entry' => $this->getCalendarEntry($id)]);
        if (!$calendarEntryParticipationForm->entry->content->canEdit()) {
            throw new HttpException(403, 'You cannot edit the event!');
        }

        if ($calendarEntryParticipationForm->load(Yii::$app->request->post()) && $calendarEntryParticipationForm->save()) {
            return ModalClose::widget(['saved' => true]);
        }

        return $this->renderModalParticipation($calendarEntryParticipationForm->entry);
    }

    /**
     * Action to render modal window with participation settings and active tab "Participants of the event"
     *
     * @param int|null $id
     * @return string
     */
    public function actionModalParticipants($id = null)
    {
        return $this->renderModalParticipation($this->getCalendarEntry($id), 'list');
    }

    /**
     * Action to render only participants list
     * Used for filtering and pagination
     *
     * @param int|null $id
     * @return string
     */
    public function actionParticipantsList($id = null)
    {
        return $this->renderAjax('edit-participants', [
            'calendarEntryParticipationForm' => new CalendarEntryParticipationForm(['entry' => $this->getCalendarEntry($id)]),
            'form' => null,
            'renderWrapper' => false,
        ]);
    }

    /**
     * Action to export participants
     *
     * @param int $id
     * @param int|null $state
     * @param string $type File format type: 'csv' or 'xlsx'
     * @return Response
     */
    public function actionExportParticipants(int $id, ?int $state, string $type)
    {
        $entry = $this->getCalendarEntry($id);

        if (!$entry->content->canEdit()) {
            throw new ForbiddenHttpException('You cannot export participants of the event!');
        }

        if (!in_array($type, ['csv', 'xlsx'])) {
            throw new BadRequestHttpException('Wrong export type "' . $type . '"');
        }

        return $entry->participation->exportParticipants($state, $type);
    }

    public function actionSearchParticipants(int $entryId, string $keyword)
    {
        $content = $this->getCalendarEntry($entryId)->content;

        if (!$content->canEdit()) {
            throw new HttpException(403);
        }

        $filterParams = [
            'keyword' => $keyword,
            'fillUser' => true,
        ];

        if (!$content->isPublic()) {
            $filterParams['filter'] = function (array &$userData) use ($content) {
                if (!$userData['disabled'] && !$content->canView($userData['id'])) {
                    $userData['disabled'] = true;
                    $userData['disabledText'] = Yii::t('CalendarModule.base', 'Private events are only visible to you and, if the friendship system is activated, to your friends.');
                }
            };
        }

        return $this->asJson(UserPicker::filter($filterParams));
    }

    public function actionAddParticipants()
    {
        $this->forcePostRequest();

        $entryId = Yii::$app->request->post('entryId');
        $status = Yii::$app->request->post('status');

        if (!ParticipantItem::hasStatus($status)) {
            throw new HttpException(403, 'Wrong status!');
        }

        $entry = $this->getCalendarEntry($entryId);
        if (!$entry->content->canEdit()) {
            throw new HttpException(403);
        }

        if ($status == CalendarEntryParticipant::PARTICIPATION_STATE_INVITED && !$entry->canInvite()) {
            throw new HttpException(403, Yii::t('CalendarModule.base', 'You cannot invite participants!'));
        }

        return $this->addParticipants($entry, $status);
    }

    private function addParticipants(CalendarEntry $entry, $status): Response
    {
        $guids = Yii::$app->request->post('guids');

        if (empty($guids)) {
            return $this->asJson([
                'error' => Yii::t('CalendarModule.base', 'Please select new participants.'),
            ]);
        }

        $users = User::find()
            ->leftJoin('calendar_entry_participant', 'user.id = user_id AND calendar_entry_id = :entry_id', ['entry_id' => $entry->id])
            ->where(['IN', 'guid', $guids])
            ->andWhere(['IS', 'user_id', new Expression('NULL')])
            ->all();

        $isInvitation = ($status == CalendarEntryParticipant::PARTICIPATION_STATE_INVITED);

        if (empty($users)) {
            return $this->asJson([
                'warning' => $isInvitation
                    ? Yii::t('CalendarModule.base', 'No new participants were invited.')
                    : Yii::t('CalendarModule.base', 'No new participants were added.'),
            ]);
        }

        $addedUserNames = [];
        $newParticipantsHtml = [];
        foreach ($users as $u => $user) {
            if ($entry->participation->setParticipationStatus($user, $status)) {
                $addedUserNames[] = $user->displayName;
                $newParticipantsHtml[] = ParticipantItem::widget([
                    'entry' => $entry,
                    'user' => $user,
                ]);
            } else {
                unset($users[$u]);
            }
        }

        if ($isInvitation && count($users)) {
            ParticipantAdded::instance()->from(Yii::$app->user->getIdentity())->about($entry)->sendBulk($users);
        }

        $successMessageParams = ['users' => implode(', ', $addedUserNames)];

        return $this->asJson([
            'success' => $isInvitation
                ? Yii::t('CalendarModule.base', 'Invited: {users}', $successMessageParams)
                : Yii::t('CalendarModule.base', 'Added: {users}', $successMessageParams),
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

        $result = $entry->participation->setParticipationStatus($user, $status);

        return $this->asJson([
            'success' => $result,
            'message' => $result
                ? Yii::t('CalendarModule.base', 'Status updated.')
                : Yii::t('CalendarModule.base', 'Status cannot be updated.'),
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

        if (!$entry) {
            throw new HttpException(404);
        }

        if (!$entry->content->canEdit()) {
            throw new HttpException(403);
        }

        $entry->toggleClosed();

        return $this->asJson(Stream::getContentResultEntry($entry->content));
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

        // Allow to delete the entry even if it was already soft deleted before by unknown issue
        $calendarEntry = $this->getCalendarEntry($id, Content::STATE_DELETED);

        if (!$calendarEntry) {
            throw new HttpException('404', Yii::t('CalendarModule.base', "Event not found!"));
        }

        if (!$calendarEntry->content->canEdit()) {
            throw new HttpException('403', Yii::t('CalendarModule.base', "You don't have permission to delete this event!"));
        }

        if (!$calendarEntry->delete()) {
            return Yii::$app->request->isAjax
                ? $this->asJson([
                    'success' => false,
                    'message' => Yii::t('CalendarModule.base', 'Event could not be deleted!'),
                ])
                : $this->redirect(Url::toEntry($calendarEntry));
        }

        return Yii::$app->request->isAjax
            ? $this->asJson([
                'success' => true,
                'message' => Yii::t('CalendarModule.base', 'Event has been be deleted!'),
            ])
            : $this->redirect(Url::toCalendar($this->contentContainer));
    }

    /**
     * Returns a readable calendar entry by given id
     *
     * @param int $id
     * @param array|string|null $allowedStateFilters
     * @return CalendarEntry
     * @throws Throwable
     * @throws Exception
     */
    protected function getCalendarEntry($id, $allowedStateFilters = null): CalendarEntry
    {
        if (!$id) {
            throw new HttpException(404);
        }

        $query = CalendarEntry::find()->contentContainer($this->contentContainer);
        if ($allowedStateFilters) {
            $query->stateFilterCondition[] = ['content.state' => $allowedStateFilters];
        }

        /* @var CalendarEntry $entry */
        $entry = $query->readable()->where(['calendar_entry.id' => $id])->one();

        if (!$entry) {
            throw new NotFoundHttpException();
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
        return (new CalendarEntry($this->contentContainer))->content->canEdit();
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
}
