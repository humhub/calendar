<?php

namespace humhub\modules\calendar\controllers;

use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\models\recurrence\CalendarRecurrenceExpand;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\HttpException;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\stream\actions\Stream;
use humhub\widgets\ModalClose;
use humhub\modules\user\models\User;
use humhub\modules\user\widgets\UserListBox;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\calendar\permissions\CreateEntry;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use yii\web\NotFoundHttpException;

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

        $recurrence = $recurrenceRoot->getEventQuery()->getRecurrenceInstance($recurrence_id);

        if(!$recurrence) {
            $recurrence = $recurrenceRoot->getEventQuery()->expandSingle($recurrence_id);
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
     * @return \yii\web\Response
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
            } else {
                return $this->renderModal($calendarEntryForm->entry, 1);
            }
        } elseif(!Yii::$app->request->post()) { // Set default time for all day events creation other than 00:00, 23:59
            $calendarEntryForm->setDefaultTime();
        }

        return $this->renderAjax('edit', [
            'calendarEntryForm' => $calendarEntryForm,
            'contentContainer' => $this->contentContainer,
            'editUrl' => Url::toEditEntry($calendarEntryForm->entry, $cal, $this->contentContainer)
        ]);
    }

    /**
     * @param $id
     * @return \yii\web\Response
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
     * @return \yii\web\Response
     * @throws HttpException
     * @throws Throwable
     * @throws Exception
     */
    public function actionEditAjax($id)
    {
        $this->forcePostRequest();

        $entry = $this->getCalendarEntry($id);

        if (!$entry) {
            throw new HttpException('404');
        }

        if (!$entry->content->canEdit()) {
            throw new HttpException('403');
        }

        $entryForm = new CalendarEntryForm(['entry' => $entry, 'timeZone' => CalendarUtils::getUserTimeZone(true)]);
        $start = Yii::$app->request->post('start');
        $end = Yii::$app->request->post('end');

        if ($entryForm->updateDateRangeFromCalendar($start, $end, CalendarUtils::getUserTimeZone(), true)) {
            return $this->asJson(['success' => true]);
        }

        throw new HttpException(400, "Could not save! " . print_r($entry->getErrors()));
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
     * @return EntryController|\yii\console\Response|\yii\web\Response
     * @throws HttpException
     * @throws Throwable
     * @throws Exception
     * @throws \yii\db\StaleObjectException
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
        return CalendarEntry::find()->contentContainer($this->contentContainer)->readable()->where(['calendar_entry.id' => $id])->one();
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
     * @return \yii\console\Response|\yii\web\Response
     * @throws Throwable
     * @throws Exception
     * @throws \yii\web\RangeNotSatisfiableHttpException
     */
    public function actionGenerateics()
    {
        $calendarEntry = $this->getCalendarEntry(Yii::$app->request->get('id'));
        $ics = $calendarEntry->generateIcs();
        return Yii::$app->response->sendContentAsFile($ics, uniqid() . '.ics', ['mimeType' => 'text/calendar']);
    }
}
