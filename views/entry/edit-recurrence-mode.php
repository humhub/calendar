<?php

use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\ui\view\components\View;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use humhub\modules\calendar\helpers\Url;

/* @var $this View */
/* @var $model RecurrenceFormModel */
/* @var $form ActiveForm */

$entry = $model->entry;

/** @var CalendarEventIF $root */
$root = $entry->getRecurrenceQuery()->getRecurrenceRoot();

?>

<?php if(RecurrenceHelper::isRecurrentInstance($entry)) : ?>

    <?= $form->field($model, 'recurrenceEditMode')->hiddenInput(['id' => 'recurrenceEditMode'])->label(false) ?>

    <div class="modal-body recurrence-edit-type">

        <?= Button::info(Yii::t('CalendarModule.recurrence', 'Edit this event'))
            ->options(['data-edit-mode' => RecurrenceFormModel::EDIT_MODE_THIS ])
            ->action('setEditMode')
            ->style('width:100%')->lg()->loader(false)?>

        <br>
        <br>

        <?php if(true) : ?>

            <?= Button::info(Yii::t('CalendarModule.recurrence', 'Edit this and following events'))
                ->options(['data-edit-mode' => RecurrenceFormModel::EDIT_MODE_FOLLOWING ])
                ->action('setEditMode')
                ->style('width:100%')->lg()->loader(false)?>

            <br>
            <br>
        <?php endif; ?>

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

<?php endif; ?>