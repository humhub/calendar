<?php

use yii\helpers\Html;
use humhub\widgets\Link;
use yii\helpers\Url;

?>

<div class="modal-body">
    <p class="form-heading">
        <?= Yii::t('CalendarModule.export', 'This CalDAV link lets you sync events both ways with your calendar app. It supports two-way sync-changes made in your calendar app will be reflected in HumHub, and vice versa. Some calendar apps require the base URL, while others only need the full CalDAV URL. Use the credentials from your HumHub account when prompted.') ?>
    </p>
    <div class="form-group">
        <?= Html::label(Yii::t('CalendarModule.export', 'CalDAV URL')); ?>
        <?= Html::textInput(null, Url::to(['/calendar/remote/cal-dav'], true), ['disabled' => true, 'class' => 'form-control']) ?>
        <div class="text-right help-block">
            <div id="url_2" class="hidden"><?= Url::to(['/calendar/remote/cal-dav'], true) ?></div>
            <?= Link::withAction(Yii::t('CalendarModule.export', 'Copy to clipboard'), 'copyToClipboard', null, '#url_2')->icon('fa-clipboard')->style('color:#777') ?>
        </div>
    </div>
    <div class="form-group">
        <?= Html::label(Yii::t('CalendarModule.export', 'CalDAV Base URL')); ?>
        <?= Html::textInput(null, Url::to(['/'], true), ['disabled' => true, 'class' => 'form-control']) ?>
        <div class="text-right help-block">
            <div id="url_3" class="hidden"><?= Url::to(['/'], true) ?></div>
            <?= Link::withAction(Yii::t('CalendarModule.export', 'Copy to clipboard'), 'copyToClipboard', null, '#url_3')->icon('fa-clipboard')->style('color:#777') ?>
        </div>
    </div>
    <div class="form-group">
        <?= Html::label(Yii::t('CalendarModule.export', 'Username')); ?>
        <?= Html::textInput(null, Yii::$app->user->identity->username, ['disabled' => true, 'class' => 'form-control']) ?>
        <div class="text-right help-block">
            <div id="url_4" class="hidden"><?= Yii::$app->user->identity->username ?></div>
            <?= Link::withAction(Yii::t('CalendarModule.export', 'Copy to clipboard'), 'copyToClipboard', null, '#url_4')->icon('fa-clipboard')->style('color:#777') ?>
        </div>
    </div>
    <div class="form-group">
        <?= Html::label(Yii::t('CalendarModule.export', 'Password')); ?>
        <?= Html::textInput(null, Yii::t('CalendarModule.export', 'Your HumHub password'), ['disabled' => true, 'class' => 'form-control']) ?>
    </div>
</div>
