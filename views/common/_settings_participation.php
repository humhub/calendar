<?php

use humhub\components\View;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\models\participation\ParticipationSettings;
use humhub\widgets\Button;

/* @var $this View */
/* @var $participationSettings ParticipationSettings */

$helpBlock = $participationSettings->isGlobal()
    ? Yii::t('CalendarModule.config', 'Here you can configure default settings for new calendar events. These settings can be overwritten on space/profile level.')
    : Yii::t('CalendarModule.config', 'Here you can configure default settings for new calendar events.') ;

?>

<div class="panel-body" data-ui-widget="calendar.Form">
    <h4>
        <?= Yii::t('CalendarModule.config', 'Default participation settings'); ?>
        <?php if ($participationSettings->showResetButton()) : ?>
            <?= Button::defaultType(Yii::t('CalendarModule.config', 'Reset'))
                ->action('client.pjax.post', $participationSettings->getResetButtonUrl())->link()->right()->sm()?>
        <?php endif; ?>
    </h4>

    <div class="help-block">
        <?= $helpBlock ?>
    </div>

    <?= $form->field($participationSettings, 'participation_mode')->dropDownList(CalendarEntryForm::getParticipationModeItems(), ['data-action-change' => 'changeParticipationMode']) ?>
    <div class="participationOnly" style="<?= $participationSettings->isParticipationAllowed() ? '' : 'display:none' ?>">
        <?= $form->field($participationSettings, 'allow_decline')->checkbox() ?>
        <?= $form->field($participationSettings, 'allow_maybe')->checkbox() ?>
    </div>
</div>
