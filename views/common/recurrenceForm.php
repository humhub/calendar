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

    <?= $form->field($model, 'repeatIntervalValue')->input('number', ['min' => 1]) ?>
    <?= $form->field($model, 'repeatIntervalType')->dropDownList($model->getIntervalTypesSelection()) ?>

    <div class="rrule-weekly">
        <?= $form->field($model, 'weekDays')->widget(MultiSelect::class, ['items' => CalendarUtils::getDaysOfWeek()])?>
    </div>

    <div class="rrule-monthly">
        <?= $form->field($model, 'monthDaySelection')->dropDownList($model->getMonthlyDaySelection()) ?>
    </div>

    <div class="rrule-end">
        <?= $form->field($model, 'monthDaySelection')->dropDownList($model->getEndTypeSelection()) ?>
    </div>

<?= Html::endTag('div') ?>
