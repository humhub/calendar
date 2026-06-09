<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\widgets\bootstrap\Alert;
use humhub\widgets\form\ActiveForm;

/* @var $form ActiveForm */
/* @var $calendarEntryParticipationForm CalendarEntryParticipationForm */

$entry = $calendarEntryParticipationForm->entry;
?>
<?= $form->field($entry, 'participation_mode')
    ->dropDownList(CalendarEntryParticipationForm::getModeItems(), ['data-action-change' => 'changeParticipationMode']) ?>

<div class="participationOnly<?= $entry->participation->isEnabled() ? '' : ' d-none' ?>">
    <?= $form->field($entry, 'max_participants')->textInput() ?>
    <?= $form->field($entry, 'allow_decline')->checkbox() ?>
    <?= $form->field($entry, 'allow_maybe')->checkbox() ?>
    <?= $form->field($entry, 'participant_info')->widget(RichTextField::class, ['placeholder' => Yii::t('CalendarModule.base', 'Additional Information for participants'), 'pluginOptions' => ['maxHeight' => '300px']]) ?>
    <?php if ($entry->online) : ?>
        <?= $form->field($entry, 'participation_url') ?>
        <?= Alert::info(Yii::t('CalendarModule.base', 'Participantion information and participantion link are only visible to users marked as <strong>Attending</strong> or <strong>Maybe</strong>.'))->closeButton(false) ?>
    <?php endif ?>

    <?php if (!$entry->isNewRecord) : ?>
        <?= $form->field($calendarEntryParticipationForm, 'sendUpdateNotification')->checkbox() ?>
    <?php endif; ?>
</div>
