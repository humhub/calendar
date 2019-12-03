<?php

use humhub\components\View;
use humhub\libs\Html;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\ui\form\widgets\DatePicker;
use humhub\modules\ui\form\widgets\MultiSelect;
use Recurr\Frequency;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $form ActiveForm */
/* @var $model RecurrenceFormModel() */
/* @var $options [] */

?>

<?= Html::beginTag('div', $options) ?>

    <label class="control-label"><?= $model->getAttributeLabel('frequency') ?></label>
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

    <div class="rrule-weekly hideIfNoRecurrence" data-recurrence-type="<?= Frequency::WEEKLY ?>">
        <?= $form->field($model, 'weekDays')->widget(MultiSelect::class, ['items' => CalendarUtils::getDaysOfWeek()]) ?>
    </div>

    <div class="rrule-monthly hideIfNoRecurrence" data-recurrence-type="<?= Frequency::MONTHLY ?>">
        <?= $form->field($model, 'monthDaySelection')->dropDownList($model->getMonthDaySelection())->label(false) ?>
    </div>

    <div class="rrule-end hideIfNoRecurrence">
        <?= $form->field($model, 'end')->dropDownList($model->getEndTypeSelection(), ['data-action-change' => 'updatedEnd']) ?>

        <div class="recurrence-end-date" style="width:130px">
            <?= $form->field($model, 'endDate')->widget(DatePicker::class)->label(false) ?>
        </div>

        <div class="recurrence-end-occurrences" style="width:130px">
            <?= $form->field($model, 'endOccurrences')->input('number')->label(false) ?>
        </div>

    </div>

<?= Html::endTag('div') ?>
