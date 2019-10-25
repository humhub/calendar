<?php
/* @var $this \humhub\components\View */
/* @var $reminderSettings ReminderSettings */

use humhub\modules\calendar\models\forms\ReminderSettings;
use humhub\widgets\Button;

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

    <?php foreach ($model->reminderSettings->reminder as $index => $reminder): ?>
        <div class="row" data-reminder-index="<?= $index ?>">
            <div class="col-md-2">
                <?= $form->field($reminder, "[$index]unit")->dropDownList(ReminderSettings::getUnitSelection())->label(false) ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($reminder, "[$index]value")->textInput(['type' => 'number'])->label(false) ?>
            </div>
            <div class="col-md-8">
                <?= Button::danger()->action('delete')
                    ->icon('fa-times')->xs()->visible(!$reminder->isNewRecord)
                    ->style('margin: 7px 0')->loader(false) ?>

                <?= Button::primary()->action('add')
                    ->icon('fa-plus')->xs()->visible($reminder->isNewRecord)
                    ->style('margin: 7px 0')->loader(false) ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
