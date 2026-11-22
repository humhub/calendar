<?php

use humhub\components\View;
use humhub\modules\calendar\Module;
use humhub\modules\oauth\components\DeviceConnectionRegistry;
use humhub\modules\external_calendar\assets\Assets;
use humhub\modules\external_calendar\models\CalendarExport;
use humhub\widgets\bootstrap\Tabs;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/**
 * @var $this View
 * @var $token string
 */

$items = [
    ['label' => Yii::t('CalendarModule.base', 'iCal'), 'view' => 'ical'],
];
// Offered while CalDAV is available, with pretty URLs (see `Events::onCollectDeviceConnections()`)
if (DeviceConnectionRegistry::find(Yii::$app->user->identity, Module::CALDAV_CONNECTION) !== null) {
    $items[] = ['label' => Yii::t('CalendarModule.base', 'CalDAV'), 'view' => 'caldav'];
}
?>

<?php Modal::beginDialog([
    'title' => Yii::t('CalendarModule.export', '<strong>Calendar</strong> export'),
    'footer' => ModalButton::cancel(Yii::t('base', 'Close')),
]) ?>

<?= Tabs::widget([
    'viewPath' => '@calendar/views/export/modal',
    'params' => ['token' => $token],
    'items' => $items,
]) ?>

<?php Modal::endDialog() ?>
