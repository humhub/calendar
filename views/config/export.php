<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\ExportSettings;
use humhub\modules\calendar\widgets\GlobalConfigMenu;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\widgets\Button;

/* @var $model ExportSettings */
/* @var $this \yii\web\View */
?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('CalendarModule.config', '<strong>Calendar</strong> module configuration') ?></div>

    <?= GlobalConfigMenu::widget() ?>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(); ?>

        <h4><?= Yii::t('CalendarModule.config', 'Export settings') ?></h4>
        <hr>

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
