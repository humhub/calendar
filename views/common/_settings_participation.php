<?php
/* @var $this \humhub\components\View */
/* @var $model \humhub\modules\calendar\models\DefaultSettings */

use humhub\modules\calendar\models\forms\CalendarEntryForm;

$helpBlock = $model->isGlobal()
    ? Yii::t('CalendarModule.config', 'Here you can configure default settings for new calendar events. These settings can be overwritten on space/profile level.')
    : Yii::t('CalendarModule.config', 'Here you can configure default settings for new calendar events.') ;

?>

<div class="panel-body" data-ui-widget="calendar.Form">
    <h4>
        <?= Yii::t('CalendarModule.config', 'Default participation settings'); ?>
    </h4>

    <div class="help-block">
        <?= $helpBlock ?>
    </div>

    <?= $form->field($model, 'participation_mode')->dropDownList(CalendarEntryForm::getParticipationModeItems(), ['data-action-change' => 'changeParticipationMode']) ?>
    <div class="participationOnly" style="<?= $model->isParticipationAllowed() ? '' : 'display:none' ?>">
        <?= $form->field($model, 'allow_decline')->checkbox() ?>
        <?= $form->field($model, 'allow_maybe')->checkbox() ?>
    </div>
</div>
