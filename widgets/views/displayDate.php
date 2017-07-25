<?php
 /* @var $entry \humhub\modules\calendar\models\CalendarEntry */
use humhub\modules\calendar\models\forms\CalendarEntryForm;

$formatter = Yii::$app->formatter;

?>
<?php if ($entry->all_day): ?>
    <?php
    // Don't use timeZone on full day events
    $userTimeZone = $formatter->timeZone;
    $formatter->timeZone = Yii::$app->timeZone;
    ?>
    <?php if ($durationDays > 1): ?>
        <?= $formatter->asDate($start, 'long'); ?>
        - <?= $formatter->asDate($end, 'long'); ?>
    <?php else: ?>
        <?= $formatter->asDate($start, 'long'); ?>
    <?php endif; ?>
    <?php $formatter->timeZone = $userTimeZone; ?>
    <?php if(!empty($entry->time_zone) && $entry->time_zone !== $formatter->timeZone): ?>
        <?php $form = new CalendarEntryForm(['timeZone' => $entry->time_zone]) ?>
        <small style="color:inherit">(<?= $form->getTimezoneLabel() ?>)</small>
    <?php endif ?>
<?php else: ?>
    <?php if ($durationDays > 1): ?>
        <?= $formatter->asDate($start, 'long'); ?>,
        <?= $formatter->asTime($start, 'short'); ?>
        - 
        <?= $formatter->asDate($end, 'long'); ?>,
        <?= $formatter->asTime($end, 'short'); ?>
    <?php else: ?>
        <?= $formatter->asDate($start, 'long'); ?>

        (<?= $formatter->asTime($start, 'short'); ?>
        - 
        <?= $formatter->asTime($end, 'short'); ?>)
    <?php endif; ?>
<?php endif; ?>