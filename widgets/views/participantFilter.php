<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use yii\helpers\Html;

/* @var string $state */
/* @var array $statuses */
?>
<?= Html::beginTag('div', ['class' => 'calendar-entry-participants-filters']) ?>
    <?= Yii::t('CalendarModule.views_entry_view', 'Filter:') ?>
    <?= Html::dropDownList('state', $state, $statuses, [
        'data-action-change' => 'filterState',
    ]) ?>
<?= Html::endTag('div') ?>
