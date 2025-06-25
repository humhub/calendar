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
        <?= $form->field($model, 'includeUserInfo')->checkbox(); ?>

        <?= Button::save()->submit() ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
