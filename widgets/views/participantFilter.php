<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\widgets\Button;
use yii\helpers\Html;

/* @var string $state */
/* @var array $statuses */
?>
<?= Html::beginTag('div', ['class' => 'calendar-entry-participants-filters']) ?>
    <div class="calendar-entry-participants-filter-title"><?= Yii::t('CalendarModule.views', 'Filter') ?></div>
    <?php foreach ($statuses as $statusKey => $statusTitle) :
        echo Button::info($statusTitle)
            ->cssClass($statusKey == $state ? 'active' : '')->xs()
            ->action('filterState')
            ->options(['data-state' => $statusKey])
            ->loader(false);
    endforeach; ?>
<?= Html::endTag('div') ?>
