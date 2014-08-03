<div class="panel panel-default">
    <div class="panel-body">
        <?php $this->widget('application.modules.calendar.widgets.FullCalendarWidget', array(
            'canWrite' => $this->contentContainer->canWrite(),
            'loadUrl' => Yii::app()->getController()->createContainerUrl('view/loadAjax'),
            'createUrl' => Yii::app()->getController()->createContainerUrl('entry/edit', array('start_time' => '-start-', 'end_time' => '-end-', 'fullCalendar' => '1'))
        )); ?>

    </div>
</div>