<?php

use humhub\modules\ui\view\components\View;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use humhub\modules\calendar\helpers\Url;

/* @var $this View */
/* @var $model CalendarEntryForm */
/* @var $form ActiveForm */

$root = $model->entry->getRecurrenceQuery()->getRecurrenceRoot();

?>

<div class="modal-body recurrence-edit-type">

    <?= $form->field($model->recurrenceForm, 'recurrenceEditMode')->hiddenInput(['id' => 'recurrenceEditMode'])->label(false) ?>

    <?= Button::info(Yii::t('CalendarModule.recurrence', 'Edit this event'))
        ->options(['data-edit-mode' => RecurrenceFormModel::EDIT_MODE_THIS ])
        ->action('setEditMode')
        ->style('width:100%')->lg()->loader(false)?>

    <br>
    <br>

    <?= Button::info(Yii::t('CalendarModule.recurrence', 'Edit this and following events'))
        ->options(['data-edit-mode' => RecurrenceFormModel::EDIT_MODE_FOLLOWING ])
        ->action('setEditMode')
        ->style('width:100%')->lg()->loader(false)?>

    <br>
    <br>

    <?= ModalButton::info(Yii::t('CalendarModule.recurrence', 'Edit all events'))
        ->load(Url::toEditEntry($root))
        ->style('width:100%')->lg()->loader() ?>

    <br>
    <br>

    <?= ModalButton::danger(Yii::t('CalendarModule.recurrence', 'Delete all events'))
        ->post(Url::toEntryDelete($root))->confirm()
        ->style('width:100%')->lg()->loader() ?>

    <br>
    <br>

    <?= ModalButton::cancel()->style('width:100%')->lg(); ?>

</div>