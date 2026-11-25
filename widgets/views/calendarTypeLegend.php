<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntryType;
use yii\helpers\Html;

/* @var CalendarEntryType[] $calendarTypes */
?>
<div class="card content-card calendar-type-legend">
    <div class="card-header">
        <div class="card-title"><strong><?= Yii::t('CalendarModule.base', 'Event Types') ?></strong></div>
    </div>
    <div class="card-body">
        <?php foreach ($calendarTypes as $calendarType) : ?>
        <div class="calendar-type-item">
            <span class="calendar-type-color" style="background:<?= Html::encode($calendarType->color) ?>"></span>
            <?= Html::encode($calendarType->name) ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
