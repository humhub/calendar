<?php

use humhub\modules\external_calendar\assets\Assets;
use humhub\widgets\ModalDialog;
use humhub\modules\external_calendar\models\CalendarExport;
use humhub\widgets\Link;
use humhub\widgets\ModalButton;
use humhub\widgets\GridView;
use yii\helpers\Html;


/**
 * @var $this \humhub\modules\ui\view\components\View
 * @var $ical_url string
 * @var $caldav_winlin_url string
 * @var $caldav_macos_url string
 */


?>

<?php ModalDialog::begin(['header' => Yii::t('CalendarModule.export', '<strong>Calendar</strong> export')]) ?>

<div class="modal-body">
    <p class="form-heading">
            <?= Yii::t('CalendarModule.export', 'These links can be added to your calendar for syncing events. iCal updates your app with new events but doesn’t sync changes back (one-way sync). CalDAV keeps events updated in both directions (two-way sync).') ?>
    </p>
    <div class="form-group">
        <?= Html::label(Yii::t('CalendarModule.export', 'iCal URL')); ?>
        <?= Html::textInput(null, $ical_url, ['disabled' => true, 'class' => 'form-control']) ?>
        <div class="text-right help-block">
            <div id="url_1" class="hidden"><?= $ical_url ?></div>
            <?= Link::withAction(Yii::t('CalendarModule.export', 'Copy to clipboard'), 'copyToClipboard', null, '#url_1')->icon('fa-clipboard')->style('color:#777') ?>
        </div>
    </div>
    <div class="form-group">
        <?= Html::label(Yii::t('CalendarModule.export', 'CalDAV URL (Windows/Linux)')); ?>
        <?= Html::textInput(null, $caldav_winlin_url, ['disabled' => true, 'class' => 'form-control']) ?>
        <div class="text-right help-block">
            <div id="url_2" class="hidden"><?= $caldav_winlin_url ?></div>
            <?= Link::withAction(Yii::t('CalendarModule.export', 'Copy to clipboard'), 'copyToClipboard', null, '#url_2')->icon('fa-clipboard')->style('color:#777') ?>
        </div>
    </div>
    <div class="form-group">
        <?= Html::label(Yii::t('CalendarModule.export', 'CalDAV URL (macOS)')); ?>
        <?= Html::textInput(null, $caldav_macos_url, ['disabled' => true, 'class' => 'form-control']) ?>
        <div class="text-right help-block">
            <div id="url_3" class="hidden"><?= $caldav_macos_url ?></div>
            <?= Link::withAction(Yii::t('CalendarModule.export', 'Copy to clipboard'), 'copyToClipboard', null, '#url_3')->icon('fa-clipboard')->style('color:#777') ?>
        </div>
    </div>
</div>
<div class="modal-footer">
    <?= ModalButton::cancel(Yii::t('base', 'Close')) ?>
</div>
<?php ModalDialog::end() ?>
