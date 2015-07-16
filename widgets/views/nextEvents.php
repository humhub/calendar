<?php

use yii\helpers\Html;
?>
<div class="panel">

    <div class="panel-heading"><?php echo Yii::t('CalendarModule.widgets_views_nextEvents', '<strong>Upcoming</strong> events '); ?></div>
    <div class="panel-body">

        <?php foreach ($calendarEntries as $calendarEntry) : ?>
            <strong><?php echo Html::a($calendarEntry->title, $calendarEntry->content->container->createUrl('/calendar/entry/view', array('id' => $calendarEntry->id))); ?></strong><br />
            <?php echo humhub\modules\calendar\widgets\EntryDate::widget(array('calendarEntry' => $calendarEntry)); ?><br />
            <br/>
        <?php endforeach; ?>
    </div>

</div>

