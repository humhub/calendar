<?php if ($calendarEntry->all_day): ?>
    <?php if ($calendarEntry->GetDurationDays() > 1): ?>
        <?php echo Yii::app()->dateFormatter->formatDateTime($calendarEntry->start_time, 'long', false); ?> 
        - <?php echo Yii::app()->dateFormatter->formatDateTime($calendarEntry->end_time, 'long', false); ?>
    <?php else: ?>
        <?php echo Yii::app()->dateFormatter->formatDateTime($calendarEntry->start_time, 'long', false); ?>
    <?php endif; ?>
<?php else: ?>
    <?php if ($calendarEntry->GetDurationDays() > 1): ?>
        <?php echo Yii::app()->dateFormatter->formatDateTime($calendarEntry->start_time, 'long', 'short'); ?>
        - 
        <?php echo Yii::app()->dateFormatter->formatDateTime($calendarEntry->end_time, 'long', 'short'); ?>
    <?php else: ?>
        <?php echo Yii::app()->dateFormatter->formatDateTime($calendarEntry->start_time, 'long', null); ?>

        (<?php echo Yii::app()->dateFormatter->formatDateTime($calendarEntry->start_time, null, 'short'); ?>
        - 
        <?php echo Yii::app()->dateFormatter->formatDateTime($calendarEntry->end_time, null, 'short'); ?>)
    <?php endif; ?>
<?php endif; ?>