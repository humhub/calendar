<?php

use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;
use humhub\modules\calendar\helpers\Url;
use humhub\widgets\modal\ModalButton;
use yii\web\View;

/* @var $this View */
/* @var $model RecurrenceFormModel */
/* @var $form ActiveForm */

$entry = $model->entry;

/** @var CalendarEventIF $root */
$root = $entry->getRecurrenceQuery()->getRecurrenceRoot();

?>

<?php if (RecurrenceHelper::isRecurrentInstance($entry)) : ?>

    <?= $form->field($model, 'recurrenceEditMode')->hiddenInput(['id' => 'recurrenceEditMode'])->label(false) ?>

    <div class="recurrence-edit-type">

        <?= Button::accent(Yii::t('CalendarModule.base', 'Edit this event'))
            ->options(['data-edit-mode' => RecurrenceFormModel::EDIT_MODE_THIS ])
            ->action('setEditMode')
            ->style('width:100%')->lg()->loader(false) ?>

        <br>
        <br>

        <?php if(true) : ?>

            <?= Button::accent(Yii::t('CalendarModule.base', 'Edit this and following events'))
                ->options(['data-edit-mode' => RecurrenceFormModel::EDIT_MODE_FOLLOWING ])
                ->action('setEditMode')
                ->style('width:100%')->lg()->loader(false)?>

            <br>
            <br>
        <?php endif; ?>

        <?= ModalButton::accent(Yii::t('CalendarModule.base', 'Edit all events'))
            ->load(Url::toEditEntry($root))
            ->style('width:100%')->lg()->loader() ?>

        <br>
        <br>

        <?= ModalButton::danger(Yii::t('CalendarModule.base', 'Delete all events'))
            ->post(Url::toEntryDelete($root))->confirm()
            ->style('width:100%')->lg()->loader() ?>

        <br>
        <br>

        <?= ModalButton::cancel()->style('width:100%')->lg(); ?>

    </div>

<?php endif; ?>
