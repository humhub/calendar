<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\ExportSettings;
use humhub\modules\calendar\widgets\GlobalConfigMenu;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;

/* @var $model ExportSettings */
/* @var $this \yii\web\View */
?>
<div class="card content-card">
    <div class="card-header"><div class="card-title"><?= Yii::t('CalendarModule.config', '<strong>Calendar</strong> module configuration') ?></div></div>

    <?= GlobalConfigMenu::widget() ?>

    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <h4 class="card-title"><?= Yii::t('CalendarModule.config', 'Export settings') ?></h4>
        <hr>

        <?= $form->field($model, 'enableCalDav')->checkbox(['disabled' => !$model->canEnableCalDav()]); ?>

        <?= $form->field($model, 'jwtKey')->textInput(); ?>
        <?= $form->field($model, 'jwtExpire')->textInput(); ?>

        <hr>
        <?= $form->field($model, 'includeParticipantInfo')->checkbox(); ?>
        <?= $form->field($model, 'includeParticipantEmail', ['options' => ['class' => ['mb-3', $model->includeParticipantInfo ? '' : 'd-none']]])->checkbox(); ?>

        <?= Button::save()->submit() ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>

<?php

$js = <<<JS
    $('#exportsettings-includeparticipantinfo').change(function() {
        const emailCheckbox = $('#exportsettings-includeparticipantemail');
        const emailCheckboxField = $('.field-exportsettings-includeparticipantemail');
        
        if ($(this).prop('checked')) {
            emailCheckboxField.show();
        } else {
            emailCheckbox.prop('checked', false);
            emailCheckboxField.hide();
        }
    }).trigger('change');
JS;

$this->registerJs($js);
