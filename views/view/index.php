<?php
use humhub\modules\calendar\widgets\CalendarFilterBar;
use humhub\modules\calendar\widgets\FullCalendar;
use humhub\modules\calendar\helpers\Url;

$loadAjaxUrl = Url::toAjaxLoad($contentContainer);

/* @var $filters array */
/* @var $canConfigure bool */
/* @var $canAddEntries bool */
?>
<div class="panel panel-default">
    <div class="panel-body" style="background-color:<?= $this->theme->variable('background-color-secondary') ?>">
        <?= CalendarFilterBar::widget([
            'filters' => $filters,
            'showSelectors' => false
            ]) ?>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel-body">
                <?= FullCalendar::widget([
                    'canWrite' => $canAddEntries,
                    'loadUrl' => $loadAjaxUrl,
                    'contentContainer' => $contentContainer]);
                ?>
            </div>
        </div>
    </div>
</div>