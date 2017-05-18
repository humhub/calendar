<?php
 /* @var $entry \humhub\modules\calendar\models\CalendarEntry */
?>
<?php if ($entry->all_day): ?>
    <?php
    // Don't use timeZone on full day events
    $userTimeZone = Yii::$app->formatter->timeZone;
    Yii::$app->formatter->timeZone = Yii::$app->timeZone;
    ?>
    <?php if ($durationDays > 1): ?>
        <?= Yii::$app->formatter->asDate($start, 'long'); ?> 
        - <?= Yii::$app->formatter->asDate($end, 'long'); ?>
    <?php else: ?>
        <?= Yii::$app->formatter->asDate($start, 'long'); ?>
    <?php endif; ?>
    <?php Yii::$app->formatter->timeZone = $userTimeZone; ?>
<?php else: ?>
    <?php if ($durationDays > 1): ?>
        <?= Yii::$app->formatter->asDate($start, 'long'); ?>
        (<?= Yii::$app->formatter->asTime($start, 'short'); ?>)
        - 
        <?= Yii::$app->formatter->asDate($end, 'long'); ?>
        (<?= Yii::$app->formatter->asTime($end, 'short'); ?>)
    <?php else: ?>
        <?= Yii::$app->formatter->asDate($start, 'long'); ?>

        (<?= Yii::$app->formatter->asTime($start, 'short'); ?>
        - 
        <?= Yii::$app->formatter->asTime($end, 'short'); ?>)
    <?php endif; ?>
<?php endif; ?>