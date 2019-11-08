<?php


namespace humhub\modules\calendar\interfaces\recurrence;


use humhub\widgets\JsWidget;
use yii\widgets\ActiveForm;

class RecurrenceFormWidget extends JsWidget
{
    public $jsWidget = 'calendar.recurrence.Form';

    /**
     * @var RecurrentCalendarEntry
     */
    public $model;

    /**
     * @var ActiveForm
     */
    public $form;

    public function run()
    {
        return $this->render('@calendar/views/common/recurrenceForm', [
            'model' => new RecurrenceFormModel(['entry' => $this->model]),
            'form' => $this->form,
            'options' => $this->getOptions()
        ]);
    }

}