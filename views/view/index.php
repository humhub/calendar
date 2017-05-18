<div class="panel panel-default">
    <div class="panel-body" style="background-color:<?= $this->theme->variable('background-color-secondary') ?>">
        <?= \humhub\modules\calendar\widgets\CalendarFilterBar::widget([
            'filters' => $filters,
            'showSelectors' => false
        ]) ?>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel-body">
                <?= \humhub\modules\calendar\widgets\FullCalendar::widget(array(
                    'canWrite' => $canAddEntries,
                    'loadUrl' => $contentContainer->createUrl('/calendar/view/load-ajax'),
                    'contentContainer' => $contentContainer));
                ?>
            </div>
        </div>
    </div>
</div>