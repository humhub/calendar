<?php

use humhub\modules\calendar\helpers\AuthTokenService;
use yii\helpers\Html;
use humhub\widgets\Link;
use yii\helpers\Url;

$passwordToken = AuthTokenService::instance()->calDavEncrypt(Yii::$app->user->identity);

?>

<div class="modal-body">
    <p class="form-heading">
        <?= Yii::t('CalendarModule.export', 'This CalDAV link lets you sync events both ways with your calendar app. It supports two-way sync-changes made in your calendar app will be reflected in HumHub, and vice versa. Some calendar apps require the base URL, while others only need the full CalDAV URL. Use the credentials from your HumHub account when prompted.') ?>
    </p>
    <div class="form-group">
        <?= Html::label(Yii::t('CalendarModule.export', 'CalDAV URL')); ?>
        <?= Html::textInput(null, Url::to(['/calendar/cal-dav/index'], true), ['disabled' => true, 'class' => 'form-control']) ?>
        <div class="text-right help-block">
            <div id="url_2" class="hidden"><?= Url::to(['/calendar/cal-dav/index'], true) ?></div>
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
        <?= Html::label(Yii::t('CalendarModule.export', 'Password'));?>
        <?= Html::textInput(null, $passwordToken, ['disabled' => true, 'class' => 'form-control']) ?>
        <div class="text-right help-block">
            <div id="url_5" class="hidden"><?= $passwordToken ?></div>
            <?= Link::withAction(Yii::t('CalendarModule.export', 'Copy to clipboard'), 'copyToClipboard', null, '#url_5')->icon('fa-clipboard')->style('color:#777') ?>
        </div>
        <div class="help-block"><?= Yii::t('CalendarModule.export', 'Alternatively, you can also use your password.') ?></div>
    </div>
</div>
