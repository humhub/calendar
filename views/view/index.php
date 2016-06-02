<div class="panel panel-default">
    <div class="panel-body">
        <?php
        echo \humhub\modules\calendar\widgets\FullCalendar::widget(array(
            'canWrite' => $canAddEntries,
            'loadUrl' => $contentContainer->createUrl('/calendar/view/load-ajax'),
            'createUrl' => $contentContainer->createUrl('/calendar/entry/edit', array('start_datetime' => '-start-', 'end_datetime' => '-end-', 'fullCalendar' => '1'))
        ));
        ?>

    </div>
</div>