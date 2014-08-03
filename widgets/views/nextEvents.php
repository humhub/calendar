<div class="panel">

    <div class="panel-heading"><?php echo Yii::t('CalendarModule.widgets_views_nextEvents', '<strong>Upcoming</strong> events '); ?></div>
    <div class="panel-body">

        <?php foreach ($calendarEntries as $calendarEntry) : ?>
            <strong><?php echo CHtml::link($calendarEntry->title,$calendarEntry->createContainerUrlTemp('calendar/entry/view', array('id'=>$calendarEntry->id))); ?></strong><br />
            <?php $this->widget('application.modules.calendar.widgets.CalendarEntryDateWidget', array('calendarEntry'=>$calendarEntry)); ?><br />
            <br/>
        <?php endforeach; ?>
    </div>

</div>

