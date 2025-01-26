<?php

use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\widgets\CalendarFilterBar;
use humhub\modules\calendar\widgets\CalendarTypeLegend;
use humhub\modules\calendar\widgets\FullCalendar;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\ui\view\helpers\ThemeHelper;

/* @var $filters array */
/* @var $canConfigure bool */
/* @var $canAddEntries bool */
/* @var $contentContainer ContentContainerActiveRecord */
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <?= CalendarFilterBar::widget([
            'filters' => $filters,
            'showSelectors' => false,
        ]) ?>
    </div>
    <div class="panel-body">
        <?= FullCalendar::widget([
            'canWrite' => $canAddEntries,
            'loadUrl' => Url::toAjaxLoad($contentContainer),
            'contentContainer' => $contentContainer,
            'aspectRatio' => ThemeHelper::isFluid() ? 2 : 1.7,
        ]) ?>
    </div>
</div>

<?= CalendarTypeLegend::widget(['contentContainer' => $contentContainer]) ?>
