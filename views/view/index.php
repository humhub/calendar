<?php
use humhub\modules\calendar\widgets\CalendarFilterBar;
use humhub\modules\calendar\widgets\FullCalendar;
use humhub\widgets\Button;
use humhub\widgets\FadeIn;

$configUrl = $contentContainer->createUrl('/calendar/container-config');
$loadAjaxUrl = $contentContainer->createUrl('/calendar/view/load-ajax');
?>
<div class="panel panel-default">
    <div class="panel-body" style="background-color:<?= $this->theme->variable('background-color-secondary') ?>">
        <?= CalendarFilterBar::widget([
            'filters' => $filters,
            'canConfigure' => $canConfigure,
            'configUrl' => $configUrl,
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