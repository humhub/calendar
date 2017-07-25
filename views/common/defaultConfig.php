<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
/* @var $this yii\web\View */
/* @var $model \humhub\modules\calendar\models\DefaultSettings */

\humhub\modules\calendar\assets\Assets::register($this);

use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\widgets\ContainerConfigMenu;
use humhub\modules\calendar\widgets\GlobalConfigMenu;
use humhub\widgets\ActiveForm;
use humhub\widgets\Button;
use humhub\widgets\Tabs;
use \yii\helpers\Html;

$helpBlock = $model->isGlobal()
    ? Yii::t('CalendarModule.config', 'Here you can configure default settings for new calendar events. These settings can be overwritten on space/profile level.')
    : Yii::t('CalendarModule.config', 'Here you can configure default settings for new calendar events.') ;
?>

<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('CalendarModule.config', '<strong>Calendar</strong> module configuration'); ?></div>

    <?php if($model->isGlobal()) : ?>
        <?= GlobalConfigMenu::widget() ?>
    <?php else: ?>
        <?= ContainerConfigMenu::widget()?>
    <?php endif; ?>

    <div class="panel-body" data-ui-widget="calendar.Form">
        <?php $form = ActiveForm::begin(['action' => $model->getSubmitUrl()]); ?>
            <h4>
                <?= Yii::t('CalendarModule.config', 'Default event settings'); ?>
            </h4>

            <div class="help-block">
                <?= $helpBlock ?>
            </div>

            <?= $form->field($model, 'participation_mode')->dropDownList(CalendarEntryForm::getParticipationModeItems(), ['data-action-change' => 'changeParticipationMode'])?>
            <div class="participationOnly" style="<?= $model->isParticipationAllowed() ? '' : 'display:none' ?>">
                <?= $form->field($model, 'allow_decline')->checkbox() ?>
                <?= $form->field($model, 'allow_maybe')->checkbox() ?>
            </div>

            <?= Button::primary(Yii::t('base', 'Save'))->submit() ?>

            <?php if($model->showResetButton()) : ?>
                <a href="<?= $model->getResetButtonUrl(); ?>" class='btn btn-default pull-right' data-ui-loader><?= Yii::t('CalendarModule.config', 'Reset'); ?></a>
            <?php endif; ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
