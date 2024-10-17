<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\calendar\assets\CalendarBaseAssets;
use humhub\modules\calendar\models\DefaultSettings;
use humhub\modules\calendar\widgets\ContainerConfigMenu;
use humhub\modules\calendar\widgets\GlobalConfigMenu;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\widgets\Button;
use humhub\widgets\Tabs;

/* @var $this yii\web\View */
/* @var $model DefaultSettings */

CalendarBaseAssets::register($this);

?>

<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('CalendarModule.config', '<strong>Calendar</strong> module configuration'); ?></div>

    <?php if ($model->isGlobal()) : ?>
        <?= GlobalConfigMenu::widget() ?>
    <?php else: ?>
        <?= ContainerConfigMenu::widget() ?>
    <?php endif; ?>


    <?php $form = ActiveForm::begin(['action' => $model->getSubmitUrl()]); ?>

    <?= Tabs::widget([
        'viewPath' => '@calendar/views/common',
        'params' => [
            'form' => $form,
            'basicSettings' => $model->basicSettings,
            'participationSettings' => $model->participationSettings,
            'reminderSettings' => $model->reminderSettings,
            'fullCalendarSettings' => $model->fullCalendarSettings
        ],
        'items' => [
            ['label' => Yii::t('CalendarModule.config', 'Basic'), 'view' => '_settings_basic', 'linkOptions' => ['class' => 'tab-basic']],
            ['label' => Yii::t('CalendarModule.config', 'Participation'), 'view' => '_settings_participation', 'linkOptions' => ['class' => 'tab-participation']],
            ['label' => Yii::t('CalendarModule.config', 'Reminder'), 'view' => '_settings_reminder', 'linkOptions' => ['class' => 'tab-participation']],
            ['label' => Yii::t('CalendarModule.config', 'Full calendar'), 'view' => '_settings_full_calendar', 'linkOptions' => ['class' => 'tab-full-calendar']],
        ]
    ]); ?>

    <hr>

    <div class="panel-body">
        <?= Button::primary(Yii::t('base', 'Save'))->submit() ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
