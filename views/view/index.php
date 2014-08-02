
<div class="panel panel-default">
    <div class="panel-body">

        <h1>Calendar</h1>
        <script>
            $(document).ready(function() {

                var jsonDateFormat = "YYYY-MM-DD HH:mm:ss";

                var calendar = $('#calendar').fullCalendar({
                    timezone: "<?php echo date_default_timezone_get(); ?>",
                    lang: '<?php echo Yii::app()->language; ?>',
                    defaultView: 'agendaWeek',
                    header: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'month,agendaWeek,agendaDay'
                    },
                    editable: <?php echo Yii::app()->getController()->contentContainer->canWrite() ? 'true' : 'false'; ?>,
                    events: {
                        url: '<?php echo Yii::app()->getController()->createContainerUrl('view/loadAjax'); ?>',
                        error: function() {
                            alert("loading error!");
                        }
                    },
                    selectable: <?php echo Yii::app()->getController()->contentContainer->canWrite() ? 'true' : 'false'; ?>,
                    selectHelper: <?php echo Yii::app()->getController()->contentContainer->canWrite() ? 'true' : 'false'; ?>,
                    select: function(start, end) {
                        var editUrl = '<?php echo Yii::app()->getController()->createContainerUrl('entry/edit', array('start_time' => '-start-', 'end_time' => '-end-', 'fullCalendar' => '1')); ?>';
                        editUrl = editUrl.replace('-start-', encodeURIComponent(start.format(jsonDateFormat)));
                        editUrl = editUrl.replace('-end-', encodeURIComponent(end.format(jsonDateFormat)));
                        $('#globalModal').modal({
                            show: 'true',
                            remote: editUrl
                        });
                        calendar.fullCalendar('unselect');
                    },
                    eventResize: function(event, delta, revertFunc) {
                        var editUrl = '<?php echo Yii::app()->getController()->createContainerUrl('entry/editAjax', array('id' => '-id-', 'end_time' => '-end-', 'fullCalendar' => '1')); ?>';
                        editUrl = editUrl.replace('-end-', encodeURIComponent(event.end.format(jsonDateFormat)));
                        editUrl = editUrl.replace('-id-', encodeURIComponent(event.id));
                        $.ajax({
                            url: editUrl
                        });
                    },
                    eventDrop: function(event, delta, revertFunc) {

                        var editUrl = '<?php echo Yii::app()->getController()->createContainerUrl('entry/editAjax', array('id' => '-id-', 'start_time' => '-start-', 'end_time' => '-end-', 'fullCalendar' => '1')); ?>';
                        editUrl = editUrl.replace('-start-', encodeURIComponent(event.start.format(jsonDateFormat)));
                        editUrl = editUrl.replace('-end-', encodeURIComponent(event.end.format(jsonDateFormat)));
                        editUrl = editUrl.replace('-id-', encodeURIComponent(event.id));
                        $.ajax({
                            url: editUrl
                        });
                    },
                    eventClick: function(event, element) {
                        var viewUrl = '<?php echo Yii::app()->getController()->createContainerUrl('entry/view', array('id' => '-id-')); ?>';
                        viewUrl = viewUrl.replace('-id-', encodeURIComponent(event.id));
                        window.location = viewUrl;
                        /*
                         $('#globalModal').modal({
                         show: 'true',
                         remote: editUrl
                         });
                         */
                    },
                    loading: function(bool) {
                        $('#loading').toggle(bool);
                    }
                });
            });
        </script>
        <style>

        </style>

        <div id='calendar'></div>
        <div id='loading'>loading...</div>



        <?php if ($entryId != ""): ?>
            <script>
                $(document).ready(function() {

                    var viewUrl = '<?php echo Yii::app()->getController()->createContainerUrl('entry/view', array('id' => '-id-')); ?>';
                    viewUrl = viewUrl.replace('-id-', '<?php echo urlencode($entryId); ?>');
                    $('#globalModal').modal({
                        show: 'true',
                        remote: viewUrl
                    });

                });
            </script>
        <?php endif; ?>
    </div>
</div>