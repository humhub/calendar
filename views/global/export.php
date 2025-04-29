<?php

use humhub\modules\external_calendar\assets\Assets;
use humhub\widgets\ModalDialog;
use humhub\modules\external_calendar\models\CalendarExport;
use humhub\widgets\ModalButton;
use humhub\widgets\Tabs;

/**
 * @var $this \humhub\modules\ui\view\components\View
 * @var $jwtEnabled bool
 * @var $iCalUrl string
 */

$tabs = [];

if ($jwtEnabled) {
    $tabs[] = ['label' => Yii::t('CalendarModule.base', 'iCal'), 'view' => 'ical'];
}
$tabs[] = ['label' => Yii::t('CalendarModule.base', 'CalDAV'), 'view' => 'caldav'];

?>

<?php ModalDialog::begin(['header' => Yii::t('CalendarModule.export', '<strong>Calendar</strong> export')]) ?>

<?= Tabs::widget([
    'viewPath' => '@calendar/views/global/export',
    'params' => ['iCalUrl' => $iCalUrl],
    'items' => $tabs,
]); ?>

<div class="modal-footer">
    <?= ModalButton::cancel(Yii::t('base', 'Close')) ?>
</div>
<?php ModalDialog::end() ?>
