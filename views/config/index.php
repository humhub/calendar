<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
/* @var $this yii\web\View */
/* @var $model \humhub\modules\calendar\models\ModuleSettings */

use yii\widgets\ActiveForm;
use \yii\helpers\Html;
?>

<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('CalendarModule.config', '<strong>Calendar</strong> module configuration'); ?></div>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(); ?>
        
        <h4>
            <?= Yii::t('CalendarModule.config', 'Upcoming events snippet'); ?>
        </h4>
        
        <div class="help-block">
            <?= Yii::t('CalendarModule.config', 'Adds an snippet with upcoming events to your users dashboard.') ?>
        </div>
        
        <?= $form->field($model, 'upcomingEventsSnippetShow')->checkbox(); ?>
        <?= $form->field($model, 'upcomingEventsSnippetDuration')->dropDownList($model->getDurationItems()); ?>
        <?= $form->field($model, 'upcomingEventsSnippetMaxItems')->input('number', ['min' => 1, 'max' => 30]) ?>
        <?= $form->field($model, 'upcomingEventsSnippetSortOrder')->input('number', ['min' => 0]) ?>
        
        <hr>

        <?= $form->field($model, 'showIfInstalled')->checkbox()
                ->hint(Yii::t('CalendarModule.config', 'If activated, the calendar top menu item and dashboard snippet is only visible for users having the calendar module installed in their profile.'));
        ?>

        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
