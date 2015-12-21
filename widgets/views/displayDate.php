<?php if ($calendarEntry->all_day): ?>
    <?php
    // Don't use timeZone on full day events
    $userTimeZone = Yii::$app->formatter->timeZone;
    Yii::$app->formatter->timeZone = Yii::$app->timeZone;
    ?>
    <?php if ($durationDays > 1): ?>
        <?php echo Yii::$app->formatter->asDate($start, 'long'); ?> 
        - <?php echo Yii::$app->formatter->asDate($end, 'long'); ?>
    <?php else: ?>
        <?php echo Yii::$app->formatter->asDate($start, 'long'); ?>
    <?php endif; ?>
    <?php Yii::$app->formatter->timeZone = $userTimeZone; ?>
<?php else: ?>
    <?php if ($durationDays > 1): ?>
        <?php echo Yii::$app->formatter->asDate($start, 'long'); ?>
        (<?php echo Yii::$app->formatter->asTime($start, 'short'); ?>)
        - 
        <?php echo Yii::$app->formatter->asDate($end, 'long'); ?>
        (<?php echo Yii::$app->formatter->asTime($end, 'short'); ?>)
    <?php else: ?>
        <?php echo Yii::$app->formatter->asDate($start, 'long'); ?>

        (<?php echo Yii::$app->formatter->asTime($start, 'short'); ?>
        - 
        <?php echo Yii::$app->formatter->asTime($end, 'short'); ?>)
    <?php endif; ?>
<?php endif; ?>