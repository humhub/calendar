<?php

use humhub\components\View;
use humhub\libs\Html;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\ui\form\widgets\MultiSelect;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $form ActiveForm */
/* @var $model RecurrenceFormModel() */
/* @var $options [] */

?>

<?= Html::beginTag('div', $options) ?>

    <label class="control-label"><?= Yii::t('CalendarModule.recurrence', 'Repeat every')?></label>
    <div class="row">
        <div class="col-md-2 hideIfNoRecurrence" style="padding-right:0">
            <?= $form->field($model, 'interval')->input('number', ['min' => 1, 'data-action-change' => 'updatedValue'])->label(false) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'frequency')->dropDownList($model->getIntervalTypesSelection(), [
                    'options' => $model->getIntervalTypesSelectionData(),
                    'data-action-change' => 'updatedType'
            ])->label(false) ?>
        </div>
        <div class="col-md-6">
        </div>
    </div>


    <div class="rrule-weekly hideIfNoRecurrence" data-recurrence-type="<?= RecurrenceFormModel::INTERVAL_WEEKLY ?>">
        <?= $form->field($model, 'weekDays')->widget(MultiSelect::class, ['items' => CalendarUtils::getDaysOfWeek()]) ?>
    </div>

    <div class="rrule-monthly hideIfNoRecurrence"  data-recurrence-type="<?= RecurrenceFormModel::INTERVAL_MONTHLY ?>">
        <?= $form->field($model, 'monthlyDay')->dropDownList($model->getMonthDaySelection()) ?>
    </div>

    <div class="rrule-end hideIfNoRecurrence">
        <?= $form->field($model, 'end')->dropDownList($model->getEndTypeSelection()) ?>
    </div>

<?= Html::endTag('div') ?>
