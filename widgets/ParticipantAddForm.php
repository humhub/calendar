<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\space\models\Space;
use humhub\modules\ui\form\widgets\ActiveForm;
use Yii;

/**
 * ParticipantAddForm to display a form to add participants to the Calendar entry (without invitation)
 */
class ParticipantAddForm extends Widget
{
    /**
     * @var ActiveForm
     */
    public $form;

    /**
     * @var CalendarEntryParticipationForm
     */
    public $model;

    /**
     * @var string
     */
    public $state;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->state === null) {
            $this->state = Yii::$app->request->get('state', Yii::$app->request->post('state', CalendarEntryParticipant::PARTICIPATION_STATE_INVITED));
        }
        $this->model->newParticipantStatus = $this->state;
        $this->model->newForceStatus = CalendarEntryParticipant::PARTICIPATION_STATE_INVITED;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (!$this->model->entry->content->canEdit()) {
            return '';
        }

        if (($this->model->entry->content->container instanceof Space) && !$this->model->entry->content->isPublic()) {
            // Search only members for private Space
            $searchUsersUrl = $this->model->entry->content->container->createUrl('/space/membership/search');
        } else {
            $searchUsersUrl = null;
        }

        return $this->render('participantAddForm', [
            'form' => $this->form,
            'model' => $this->model,
            'searchUsersUrl' => $searchUsersUrl,
            'addParticipantsUrl' => $this->model->entry->content->container->createUrl('/calendar/entry/add-participants'),
            'statuses' => ParticipantItem::getStatuses($this->model->entry, CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE)
        ]);
    }
}
