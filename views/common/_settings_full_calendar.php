<?php

use humhub\modules\calendar\assets\ParticipationFormAssets;
use humhub\modules\calendar\models\participation\FullCalendarSettings;
use yii\web\View;

/* @var $this View */
/* @var $fullCalendarSettings FullCalendarSettings */

$helpBlock = $fullCalendarSettings->isGlobal()
    ? Yii::t('CalendarModule.config', 'Here you can configure default settings for the full calendar. These settings can be overwritten on space/profile level.')
    : Yii::t('CalendarModule.config', 'Here you can configure default settings for the full calendar.');

ParticipationFormAssets::register($this);
?>

<div class="panel-body" data-ui-widget="calendar.participation.Form">
    <h4>
        <?= Yii::t('CalendarModule.config', 'Calendar default view mode settings'); ?>
    </h4>

    <div class="help-block">
        <?= $helpBlock ?>
    </div>

    <?= $form->field($fullCalendarSettings, 'viewMode')->dropDownList($fullCalendarSettings->getViewModeItems()) ?>
</div>
