<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\content\widgets\richtext\RichTextField;

/* @var $form \humhub\widgets\ActiveForm */
/* @var $calendarEntryForm \humhub\modules\calendar\models\forms\CalendarEntryForm */
?>

<div class="modal-body">
    <?= $form->field($calendarEntryForm->entry, 'participation_mode')
        ->dropDownList(CalendarEntryForm::getParticipationModeItems(), ['data-action-change' => 'changeParticipationMode'])->label(false) ?>

    <div class="participationOnly" style="<?= $calendarEntryForm->entry->isParticipationAllowed() ? '' : 'display:none' ?>">
        <?= $form->field($calendarEntryForm->entry, 'max_participants')->textInput() ?>
        <?= $form->field($calendarEntryForm->entry, 'allow_decline')->checkbox() ?>
        <?= $form->field($calendarEntryForm->entry, 'allow_maybe')->checkbox() ?>
        <?= $form->field($calendarEntryForm->entry, 'participant_info')->widget(RichTextField::class, ['placeholder' => Yii::t('CalendarModule.base', 'Participation Info'), 'pluginOptions' => ['maxHeight' => '300px']])->label(false) ?>

        <?php if(!$calendarEntryForm->entry->isNewRecord) : ?>
            <?= $form->field($calendarEntryForm, 'sendUpdateNotification')->checkbox() ?>
        <?php endif; ?>

        <?php if($calendarEntryForm->entry->canAddAll()) : ?>
            <?= $form->field($calendarEntryForm, 'forceJoin')->checkbox() ?>
        <?php endif; ?>
    </div>
</div>