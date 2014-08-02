
<div class="panel panel-default">
    <div class="panel-body">

        <h1>Calendar</h1>
        <?php $this->widget('application.modules.calendar.widgets.FullCalendarWidget'); ?>

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