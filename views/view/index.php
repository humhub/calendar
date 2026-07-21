<?php

use humhub\helpers\ThemeHelper;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\widgets\CalendarControls;
use humhub\modules\calendar\widgets\CalendarFilterBar;
use humhub\modules\calendar\widgets\CalendarTypeLegend;
use humhub\modules\calendar\widgets\ConfigureButton;
use humhub\modules\calendar\widgets\ExportButton;
use humhub\modules\calendar\widgets\FullCalendar;
use humhub\modules\content\components\ContentContainerActiveRecord;

/* @var $show string */
/* @var $canConfigure bool */
/* @var $canAddEntries bool */
/* @var $contentContainer ContentContainerActiveRecord */
?>
<div class="panel panel-default">
    <?php if (!Yii::$app->user->isGuest) : ?>
    <div class="panel-body d-flex flex-wrap gap-2">
        <div>
            <?= CalendarFilterBar::widget([
                'show' => $show,
                'showSelectors' => false,
            ]) ?>
        </div>
        <div class="ms-auto">
            <?= CalendarControls::widget([
                'widgets' => [
                    [ExportButton::class, [], ['sortOrder' => 10]],
                    [ConfigureButton::class, [], ['sortOrder' => 100]],
                ],
            ]) ?>
        </div>
    </div>
    <?php endif ?>
    <div class="panel-body">
        <?= FullCalendar::widget([
            'canWrite' => $canAddEntries,
            'loadUrl' => Url::toAjaxLoad($contentContainer),
            'contentContainer' => $contentContainer,
            'show' => $show,
            'aspectRatio' => ThemeHelper::isFluid() ? 2 : 1.7,
        ]) ?>
    </div>
</div>

<?= CalendarTypeLegend::widget(['contentContainer' => $contentContainer]) ?>
