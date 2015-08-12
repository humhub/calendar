<?php

use yii\helpers\Html;
use humhub\modules\calendar\models\CalendarEntryParticipant;

$contentContainer = $calendarEntry->content->container;
?>


<div class="media event">
    <div class="media-body">
        <div class="row">
            <div class="col-md-1"><i class="fa fa-calendar colorDefault" style="font-size: 35px;"></i><br><br></div>
            <div class="col-md-11">
                <h4 class="media-heading"><?php echo Html::encode($calendarEntry->title); ?>
                    <?php if ($calendarEntry->content->canWrite()) : ?>
                        <?php echo Html::a('<i class="fa fa-pencil"></i>', $contentContainer->createUrl('/calendar/entry/edit', array('id' => $calendarEntry->id)), array("data-target" => "#globalModal", "data-toggle" => "tooltip", "data-placement" => "top", "title" => Yii::t('CalendarModule.views_entry_view', "Edit event"), 'class' => 'tt')); ?>
                    <?php endif; ?>
                </h4>
                <h5>
                    <?php echo humhub\modules\calendar\widgets\EntryDate::widget(array('calendarEntry' => $calendarEntry)); ?>
                </h5>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?php if ($calendarEntry->description != ""): ?>
                    <?php echo nl2br(Html::encode($calendarEntry->description)); ?>
                <?php endif; ?>
                <br><br>
                <?php echo \humhub\modules\calendar\widgets\EntryParticipants::widget(array('calendarEntry' => $calendarEntry)); ?>
                <br>
                <?php if ($calendarEntry->canRespond()): ?>
                    <?php
                    $cssState = array("", "", "", "");
                    $cssState[$calendarEntry->getParticipationState()] = "disabled";
                    ?>
                    <?php echo Html::a(Yii::t('CalendarModule.views_entry_view', "Attend"), $contentContainer->createUrl('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED, 'id' => $calendarEntry->id)), array('class' => 'btn btn-info '. $cssState[3])); ?>
                    <?php echo Html::a(Yii::t('CalendarModule.views_entry_view', "Maybe"), $contentContainer->createUrl('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE, 'id' => $calendarEntry->id)), array('class' => 'btn btn-default '. $cssState[2])); ?>
                    <?php echo Html::a(Yii::t('CalendarModule.views_entry_view', "Decline"), $contentContainer->createUrl('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED, 'id' => $calendarEntry->id)), array('class' => 'btn btn-default '. $cssState[1])); ?>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

