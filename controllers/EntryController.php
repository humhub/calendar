<?php

/**
 * HumHub
 * Copyright © 2014 The HumHub Project
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 */

/**
 * EntryController used to display, edit or delete calendar entries
 *
 * @package humhub.modules_core.calendar.controllers
 * @author luke
 */
class EntryController extends ContentContainerController
{

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules()
    {
        return array(
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'users' => array('@'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    public function actionView()
    {
        $this->checkContainerAccess();

        $calendarEntry = CalendarEntry::model()->contentContainer($this->contentContainer)->findByPk(Yii::app()->request->getQuery('id'));

        if ($calendarEntry == null) {
            throw new CHttpException('404', Yii::t('CalendarModule.base', "Event not found!"));
        }

        if (!$calendarEntry->content->canRead()) {
            throw new CHttpException('403', Yii::t('CalendarModule.base', "You don't have permission to access this event!"));
        }

        $calendarEntryParticipant = CalendarEntryParticipant::model()->findByAttributes(array('user_id' => Yii::app()->user->id, 'calendar_entry_id' => $calendarEntry->id));

        $this->render('view', array(
            'calendarEntry' => $calendarEntry,
            'calendarEntryParticipant' => $calendarEntryParticipant,
            'userCanRespond' => $calendarEntry->canRespond(),
            'userAlreadyResponded' => $calendarEntry->hasResponded(),
        ));
    }

    public function actionRespond()
    {

        $this->checkContainerAccess();

        $entryId = (int) Yii::app()->request->getParam('id');
        $type = (int) Yii::app()->request->getParam('type');

        $calendarEntry = CalendarEntry::model()->contentContainer($this->contentContainer)->findByPk($entryId);

        if ($calendarEntry == null) {
            throw new CHttpException('404', Yii::t('CalendarModule.base', "Event not found!"));
        }

        if (!$calendarEntry->content->canRead()) {
            throw new CHttpException('403', Yii::t('CalendarModule.base', "You don't have permission to access this event!"));
        }

        if ($calendarEntry->canRespond()) {
            $calendarEntryParticipant = CalendarEntryParticipant::model()->findByAttributes(array('calendar_entry_id' => $calendarEntry->id, 'user_id' => Yii::app()->user->id));

            if ($calendarEntryParticipant == null) {
                $calendarEntryParticipant = new CalendarEntryParticipant;
                $calendarEntryParticipant->user_id = Yii::app()->user->id;
                $calendarEntryParticipant->calendar_entry_id = $calendarEntry->id;
            }

            $calendarEntryParticipant->participation_state = $type;
            $calendarEntryParticipant->save();
        }


        $this->redirect($this->createContainerUrl('view', array('id' => $calendarEntry->id)));
    }

    public function actionEdit()
    {
        $this->checkContainerAccess();


        // Indicates this entry is created by global calendar
        // We show a notice in this case.
        $createFromGlobalCalendar = false;

        $calendarEntry = CalendarEntry::model()->contentContainer($this->contentContainer)->findByPk(Yii::app()->request->getParam('id'));
        if ($calendarEntry == null) {

            if (!$this->contentContainer->canWrite()) {
                throw new CHttpException('403', Yii::t('CalendarModule.base', "You don't have permission to create events!"));
            }

            $calendarEntry = new CalendarEntry;

            if (Yii::app()->request->getParam('createFromGlobalCalendar') == 1) {
                $createFromGlobalCalendar = true;
            }

            if (Yii::app()->request->getParam('fullCalendar') == 1) {

                $startTime = new DateTime(Yii::app()->request->getParam('start_time', ''));
                $endTime = new DateTime(Yii::app()->request->getParam('end_time', ''));

                $calendarEntry->start_time = $startTime->format('Y-m-d H:i:s');

                if (CalendarUtils::isFullDaySpan($startTime, $endTime, true)) {
                    $calendarEntry->start_time_date = $startTime->format('Y-m-d H:i:s');

                    // In Fullcalendar the EndTime is the moment AFTER the event
                    $oneSecond = new DateInterval("PT1S");
                    $endTime->sub($oneSecond);

                    $calendarEntry->end_time_date = $endTime->format('Y-m-d H:i:s');
                    $calendarEntry->all_day = true;
                } else {
                    $calendarEntry->end_time = $endTime->format('Y-m-d H:i:s');
                }
            }
        } else {

            if (!$calendarEntry->content->canWrite()) {
                throw new CHttpException('403', Yii::t('CalendarModule.base', "You don't have permission to edit this event!"));
            }
        }

        $calendarEntry->scenario = 'edit';

        if (isset($_POST['CalendarEntry'])) {

            $calendarEntry->content->container = $this->contentContainer;
            $calendarEntry->attributes = Yii::app()->input->stripClean($_POST['CalendarEntry']);

            if ($calendarEntry->all_day) {
                $startDate = new DateTime($calendarEntry->start_time_date);
                $endDate = new DateTime($calendarEntry->end_time_date);
                $calendarEntry->start_time = $startDate->format('Y-m-d') . " 00:00:00";
                $calendarEntry->end_time = $endDate->format('Y-m-d') . " 23:59:59";
            } else {
                // Avoid "required" error, when fields are not used
                $calendarEntry->start_time_date = $calendarEntry->start_time;
                $calendarEntry->end_time_date = $calendarEntry->end_time;
            }

            if ($calendarEntry->validate()) {
                $calendarEntry->save();

                $this->renderModalClose();

                // After closing modal refresh calendar or page
                print "<script>";
                print 'if(typeof $("#calendar").fullCalendar != "undefined") { $("#calendar").fullCalendar("refetchEvents"); } else { location.reload(); }';
                print "</script>";

                return;
            }
        }

        $this->renderPartial('edit', array('calendarEntry' => $calendarEntry, 'createFromGlobalCalendar' => $createFromGlobalCalendar), false, true);
    }

    public function actionUserList()
    {
        $this->checkContainerAccess();
        $calendarEntry = CalendarEntry::model()->contentContainer($this->contentContainer)->findByPk(Yii::app()->request->getQuery('id'));

        if ($calendarEntry == null) {
            throw new CHttpException('404', Yii::t('CalendarModule.base', "Event not found!"));
        }

        if (!$calendarEntry->content->canRead()) {
            throw new CHttpException('403', Yii::t('CalendarModule.base', "You don't have permission to access this event!"));
        }

        $state = Yii::app()->request->getQuery('state');

        // Pagination
        $page = (int) Yii::app()->request->getParam('page', 1);
        $total = CalendarEntryParticipant::model()->count('calendar_entry_id=:entryId and participation_state=:state', array(':entryId' => $calendarEntry->id, ':state' => $state));
        $usersPerPage = HSetting::Get('paginationSize');
        $pagination = new CPagination($total);
        $pagination->setPageSize($usersPerPage);

        $criteria = new CDbCriteria();
        $pagination->applyLimit($criteria);
        $criteria->alias = "user";
        $criteria->join = "LEFT JOIN calendar_entry_participant on user.id = calendar_entry_participant.user_id";
        $criteria->condition = "calendar_entry_participant.calendar_entry_id = :entryId AND calendar_entry_participant.participation_state = :state";
        $criteria->params = array(':entryId' => $calendarEntry->id, ':state' => $state);

        $users = User::model()->findAll($criteria);

        $title = "";
        if ($state == CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED) {
            $title = Yii::t('CalendarModule.base', 'Attending users');
        } elseif ($state == CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED) {
            $title = Yii::t('CalendarModule.base', 'Declining users');
        } elseif ($state == CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE) {
            $title = Yii::t('CalendarModule.base', 'Maybe attending users');
        }

        $this->renderPartial('application.modules_core.user.views._listUsers', array('title' => $title, 'users' => $users, 'pagination' => $pagination), false, true);
    }

    public function actionEditAjax()
    {
        $this->checkContainerAccess();

        $calendarEntry = CalendarEntry::model()->contentContainer($this->contentContainer)->findByPk(Yii::app()->request->getQuery('id'));

        if ($calendarEntry == null) {
            throw new CHttpException('404', Yii::t('CalendarModule.base', "Event not found!"));
        }

        if (!$calendarEntry->content->canWrite()) {
            throw new CHttpException('403', Yii::t('CalendarModule.base', "You don't have permission to edit this event!"));
        }

        if ((Yii::app()->request->getParam('start_time', '') != '')) {
            $startTime = new DateTime(Yii::app()->request->getParam('start_time', ''));
            $calendarEntry->start_time = $startTime->format('Y-m-d H:i:s');
        }
        if ((Yii::app()->request->getParam('end_time', '') != '')) {
            $endTime = new DateTime(Yii::app()->request->getParam('end_time', ''));

            // If we are getting an EndTime of FullCalendar, the EndTime is the moment
            // After the Event, so we need to fix it
            if (Yii::app()->request->getParam('fullCalendar') == 1 && $calendarEntry->all_day) {
                $endTime->sub(new DateInterval('PT1S'));                // Substract an second
            }

            $calendarEntry->end_time = $endTime->format('Y-m-d H:i:s');
        }

        $calendarEntry->save();
    }

    public function actionDelete()
    {

        $this->checkContainerAccess();
        $calendarEntry = CalendarEntry::model()->contentContainer($this->contentContainer)->findByPk(Yii::app()->request->getQuery('id'));

        if ($calendarEntry == null) {
            throw new CHttpException('404', Yii::t('CalendarModule.base', "Event not found!"));
        }

        if (!$calendarEntry->content->canDelete()) {
            throw new CHttpException('403', Yii::t('CalendarModule.base', "You don't have permission to delete this event!"));
        }

        $calendarEntry->delete();

        if (Yii::app()->request->isAjaxRequest) {
            $this->renderModalClose();
        } else {
            $this->redirect($this->createContainerUrl('view/index'));
        }
    }

}
