<?php

use yii\helpers\Html;
use humhub\widgets\Link;

/**
 * @var string $ical_url
 */

?>

<div class="modal-body">
    <p class="form-heading">
        <?= Yii::t('CalendarModule.export', 'This link can be added to your calendar for syncing events. iCal updates your app with new events but doesn’t sync changes back (one-way sync).') ?>
    </p>
    <div class="form-group">
        <?= Html::label(Yii::t('CalendarModule.export', 'iCal URL')); ?>
        <?= Html::textInput(null, $ical_url, ['disabled' => true, 'class' => 'form-control']) ?>
        <div class="text-right help-block">
            <div id="url_1" class="hidden"><?= $ical_url ?></div>
            <?= Link::withAction(Yii::t('CalendarModule.export', 'Copy to clipboard'), 'copyToClipboard', null, '#url_1')->icon('fa-clipboard')->style('color:#777') ?>
        </div>
    </div>
</div>
