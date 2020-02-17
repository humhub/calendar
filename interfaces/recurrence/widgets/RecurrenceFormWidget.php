<?php


namespace humhub\modules\calendar\interfaces\recurrence\widgets;


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
     * @var RecurrentEventIF
     */
    public $entry;

    /**
     * @var ActiveForm
     */
    public $form;

    /**
     * @var RecurrenceFormModel
     */
    public $model;

    public function run()
    {
        RecurrenceFormAssets::register($this->getView());

        $model = $this->model ?: new RecurrenceFormModel(['entry' => $this->entry]);

        return $this->render('@calendar/views/common/recurrenceForm', [
            'model' => $model,
            'form' => $this->form,
            'options' => $this->getOptions()
        ]);
    }

    public function getData()
    {
        if ($this->picker) {
            return [
                'picker-selector' => $this->picker
            ];
        }

        return [];
    }

}