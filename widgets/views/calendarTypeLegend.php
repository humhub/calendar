<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntryType;

/* @var CalendarEntryType[] $calendarTypes */
?>
<div class="panel panel-default calendar-type-legend">
    <div class="panel-heading">
        <strong><?= Yii::t('CalendarModule.base', 'Event Types') ?></strong>
    </div>
    <div class="panel-body">
        <?php foreach ($calendarTypes as $calendarType) : ?>
        <div class="calendar-type-item">
            <span class="calendar-type-color" style="background:<?= $calendarType->color ?>"></span>
            <?= $calendarType->name ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
