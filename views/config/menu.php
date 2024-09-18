<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\MenuSettings;
use humhub\modules\calendar\widgets\GlobalConfigMenu;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\ui\form\widgets\SortOrderField;
use humhub\widgets\Button;

/* @var $model MenuSettings */
?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('CalendarModule.config', '<strong>Calendar</strong> module configuration') ?></div>

    <?= GlobalConfigMenu::widget() ?>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(); ?>

        <h4><?= Yii::t('CalendarModule.config', 'Menu settings') ?></h4>
        <hr>

        <?= $form->field($model, 'show')->checkbox(); ?>
        <?= $form->field($model, 'sortOrder')->widget(SortOrderField::class) ?>

        <?= Button::save()->submit() ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
