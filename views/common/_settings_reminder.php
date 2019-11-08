<?php
/* @var $this \humhub\components\View */
/* @var $model \humhub\modules\calendar\models\DefaultSettings */

$helpBlock = $model->isGlobal()
    ? Yii::t('CalendarModule.reminder', 'Here you can configure default reminder. These settings can be overwritten on space/profile level.')
    : Yii::t('CalendarModule.reminder', 'Here you can configure default settings for new calendar events.') ;

?>

<div class="panel-body" data-ui-widget="calendar.ReminderForm">
    <h4>
        <?= Yii::t('CalendarModule.resminder', 'Default reminder settings') ?>
    </h4>

    <div class="help-block">
        <?= $helpBlock ?>
    </div>

    <?= $this->render('_reminder_config', ['reminders' => $model->reminderSettings->reminders, 'form' => $form])?>

</div>
