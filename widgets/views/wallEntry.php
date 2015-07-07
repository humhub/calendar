<?php

use yii\helpers\Html;
use module\calendar\models\CalendarEntryParticipant;

$contentContainer = $calendarEntry->content->container;
?>
<?php $this->beginContent('@humhub/modules/content/views/layouts/wallLayout.php', array('object' => $calendarEntry)); ?>


<div class="pull-right">

    <?php if ($calendarEntry->canRespond() && !$calendarEntry->hasResponded()): ?>
        <?php echo Html::a(Yii::t('CalendarModule.views_entry_view', "Attend"), $contentContainer->createUrl('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED, 'id' => $calendarEntry->id)), array('class' => 'btn btn-success')); ?>
        <?php echo Html::a(Yii::t('CalendarModule.views_entry_view', "Maybe"), $contentContainer->createUrl('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE, 'id' => $calendarEntry->id)), array('class' => 'btn btn-default')); ?>
        <?php echo Html::a(Yii::t('CalendarModule.views_entry_view', "Decline"), $contentContainer->createUrl('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED, 'id' => $calendarEntry->id)), array('class' => 'btn btn-default')); ?>
    <?php endif; ?>

    <?php if ($calendarEntry->hasResponded()): ?>
        <?php
        $participationModes = array();
        $participationModes[CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED] = Yii::t('CalendarModule.views_entry_view', "I´m attending");
        $participationModes[CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE] = Yii::t('CalendarModule.views_entry_view', "I´m maybe attending");
        $participationModes[CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED] = Yii::t('CalendarModule.views_entry_view', "I´m not attending");
        ?>

        <div class="btn-group">
            <button type="button" class="btn btn-success"><?php echo $participationModes[$calendarEntryParticipant->participation_state]; ?></button>
            <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <?php
                unset($participationModes[$calendarEntryParticipant->participation_state]);
                ?>

                <?php foreach ($participationModes as $participationMode => $title): ?>
                    <li><?php echo Html::a($title, $contentContainer->createUrl('/calendar/entry/respond', array('type' => $participationMode, 'id' => $calendarEntry->id)), array('class' => '')); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <br />            
</div>


<strong>Event: <?php echo Html::encode($calendarEntry->title); ?></strong><br />
<?php echo module\calendar\widgets\EntryDate::widget(array('calendarEntry' => $calendarEntry)); ?><br />
<br />
<?php echo \module\calendar\widgets\EntryParticipants::widget(array('calendarEntry' => $calendarEntry)); ?><br />

<?php if ($calendarEntry->description != ""): ?>
    <?php echo nl2br(CHtml::encode($calendarEntry->description)); ?>
<?php endif; ?>

<?php $this->endContent(); ?>