<?php


namespace humhub\modules\calendar\interfaces\recurrence;


use humhub\modules\calendar\assets\RecurrenceFormAssets;
use humhub\widgets\JsWidget;
use yii\widgets\ActiveForm;

class RecurrenceFormWidget extends JsWidget
{
    /**
     * @inheritdoc
     */
    public $jsWidget = 'calendar.recurrence.Form';

    /**
     * @inheritdoc
     */
    public $init = true;

    /**
     * @var string jquery ui date picker selector binding, this is optional and will be used for auto updating week day option
     */
    public $picker;

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
        RecurrenceFormAssets::register($this->getView());

        return $this->render('@calendar/views/common/recurrenceForm', [
            'model' => new RecurrenceFormModel(['entry' => $this->model]),
            'form' => $this->form,
            'options' => $this->getOptions()
        ]);
    }

    public function getData()
    {
        return [
            'picker-selector' => $this->picker
        ];
    }

}