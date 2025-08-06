<?php

use humhub\widgets\bootstrap\Link;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var string $token
 */

$url = Url::to(['/calendar/export/calendar', 'token' => $token], true);
?>

<p class="form-heading">
    <?= Yii::t('CalendarModule.export', 'This link can be added to your calendar for syncing events. iCal updates your app with new events but doesn’t sync changes back (one-way sync).') ?>
</p>
<div class="mb-3">
    <?= Html::label(Yii::t('CalendarModule.export', 'iCal URL')); ?>
    <?= Html::textInput(null, $url, ['disabled' => true, 'class' => 'form-control']) ?>
    <div class="text-right help-block">
        <div id="url_1" class="hidden"><?= $url ?></div>
        <?= Link::withAction(Yii::t('CalendarModule.export', 'Copy to clipboard'), 'copyToClipboard', null, '#url_1')->icon('fa-clipboard')->style('color:#777') ?>
    </div>
</div>
