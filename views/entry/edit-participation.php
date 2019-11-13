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

$entry = $calendarEntryForm->entry;
?>

<div class="modal-body">
    <?= $form->field($entry, 'participation_mode')
        ->dropDownList(CalendarEntryForm::getParticipationModeItems(), ['data-action-change' => 'changeParticipationMode'])->label(false) ?>

    <div class="participationOnly" style="<?= $entry->participation->isEnabled() ? '' : 'display:none' ?>">
        <?= $form->field($entry, 'max_participants')->textInput() ?>
        <?= $form->field($entry, 'allow_decline')->checkbox() ?>
        <?= $form->field($entry, 'allow_maybe')->checkbox() ?>
        <?= $form->field($entry, 'participant_info')->widget(RichTextField::class, ['placeholder' => Yii::t('CalendarModule.base', 'Participation Info'), 'pluginOptions' => ['maxHeight' => '300px']])->label(false) ?>

        <?php if(!$entry->isNewRecord) : ?>
            <?= $form->field($calendarEntryForm, 'sendUpdateNotification')->checkbox() ?>
        <?php endif; ?>

        <?php if($entry->participation->canAddAll()) : ?>
            <?= $form->field($calendarEntryForm, 'forceJoin')->checkbox() ?>
        <?php endif; ?>
    </div>
</div>