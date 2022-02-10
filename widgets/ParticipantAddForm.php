<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
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
            $this->state = Yii::$app->request->get('state', Yii::$app->request->post('state', ''));
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (!$this->model->entry->content->canEdit()) {
            return '';
        }

        return $this->render('participantAddForm', [
            'form' => $this->form,
            'model' => $this->model,
            'state' => $this->state,
        ]);
    }
}
