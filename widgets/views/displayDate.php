<?php if ($calendarEntry->all_day): ?>
    <?php if ($calendarEntry->GetDurationDays() > 1): ?>
        <?php echo Yii::$app->formatter->asDate($calendarEntry->start_datetime, 'long'); ?> 
        - <?php echo Yii::$app->formatter->asDate($calendarEntry->end_datetime, 'long'); ?>
    <?php else: ?>
        <?php echo Yii::$app->formatter->asDate($calendarEntry->start_datetime, 'long'); ?>
    <?php endif; ?>
<?php else: ?>
    <?php if ($calendarEntry->GetDurationDays() > 1): ?>
        <?php echo Yii::$app->formatter->asDate($calendarEntry->start_datetime, 'long'); ?>
        (<?php echo Yii::$app->formatter->asTime($calendarEntry->start_datetime, 'short'); ?>)
        - 
        <?php echo Yii::$app->formatter->asDate($calendarEntry->end_datetime, 'long'); ?>
        (<?php echo Yii::$app->formatter->asTime($calendarEntry->end_datetime, 'short'); ?>)
    <?php else: ?>
        <?php echo Yii::$app->formatter->asDate($calendarEntry->start_datetime, 'long'); ?>

        (<?php echo Yii::$app->formatter->asTime($calendarEntry->start_datetime, 'short'); ?>
        - 
        <?php echo Yii::$app->formatter->asTime($calendarEntry->end_datetime, 'short'); ?>)
    <?php endif; ?>
<?php endif; ?>