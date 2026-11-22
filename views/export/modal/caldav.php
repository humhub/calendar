<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\Module;
use humhub\modules\oauth\components\DeviceConnectionRegistry;
use humhub\widgets\modal\ModalButton;

// The tab is only shown while the connection is offered, see `export/modal`
$connection = DeviceConnectionRegistry::find(Yii::$app->user->identity, Module::CALDAV_CONNECTION);
?>

<p class="form-heading">
    <?= Yii::t('CalendarModule.export', 'Sync your events with your calendar app (CalDAV), in both directions: changes made in the app show up in HumHub, and the other way round.') ?>
</p>
<p>
    <?= Yii::t('CalendarModule.export', 'The app signs in with a password of its own, which you create for it. You find the passwords of your devices under "Devices & Access" in your account settings.') ?>
</p>

<?= ModalButton::primary(Yii::t('CalendarModule.export', 'Connect a calendar app'))
    ->icon('plug-connected')
    ->load($connection->getUrl()) ?>
