<?php

use yii\helpers\Html;
use module\calendar\models\CalendarEntryParticipant;
use module\calendar\widgets\EntryDate;
use module\calendar\widgets\EntryParticipants;
?>
<div class="panel panel-default">
    <div class="panel-body">
        <h1>
            Event: <?php echo Html::encode($calendarEntry->title); ?>

            <?php if ($calendarEntry->is_public): ?>
                <span class="label label-success"><?php echo Yii::t('CalendarModule.views_entry_view', 'Public'); ?></span>
            <?php endif; ?>

        </h1>

        <div class="pull-right">

            <?php if ($userCanRespond && !$userAlreadyResponded): ?>
                <?php echo Html::a(Yii::t('CalendarModule.views_entry_view', "Attend"), $contentContainer->createUrl('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED, 'id' => $calendarEntry->id)), array('class' => 'btn btn-success')); ?>
                <?php echo Html::a(Yii::t('CalendarModule.views_entry_view', "Maybe"), $contentContainer->createUrl('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE, 'id' => $calendarEntry->id)), array('class' => 'btn btn-default')); ?>
                <?php echo Html::a(Yii::t('CalendarModule.views_entry_view', "Decline"), $contentContainer->createUrl('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED, 'id' => $calendarEntry->id)), array('class' => 'btn btn-default')); ?>
            <?php endif; ?>

            <?php if ($userAlreadyResponded): ?>
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

            <div>
                <br />
                <?php if ($calendarEntry->content->canWrite()) : ?>
                    <?php echo Html::a(Yii::t('CalendarModule.views_entry_view', 'Edit this event'), '#', array('class' => 'btn btn-primary btn-sm', 'onclick' => 'openEditModal(' . $calendarEntry->id . ')')); ?>
                <?php endif; ?>
            </div>
            <br />            
        </div>

        <?php echo EntryDate::widget(['calendarEntry' => $calendarEntry]); ?>

        <br /><br />

        <?php echo Yii::t('CalendarModule.views_entry_view', 'Created by:'); ?> <strong><?php echo Html::a($calendarEntry->content->user->displayName, $calendarEntry->content->user->getUrl()); ?></strong><br />

        <?php echo EntryParticipants::widget(array('calendarEntry' => $calendarEntry)); ?>

        <br />

        <?php echo nl2br(Html::encode($calendarEntry->description)); ?>


        <hr>
        <!-- <a href="#">Download ICal</a> &middot; -->
        <?php echo \humhub\modules\like\widgets\LikeLink::widget(['object' => $calendarEntry]); ?> &middot;
        <?php echo \humhub\modules\comment\widgets\CommentLink::widget(['object' => $calendarEntry]); ?>
        <?php echo \humhub\modules\comment\widgets\Comments::widget(['object' => $calendarEntry]); ?>


    </div>

</div>

<script>
    function openEditModal(id) {
        var editUrl = '<?php echo $contentContainer->createUrl('/calendar/entry/edit', array('id' => '-id-')); ?>';
        editUrl = editUrl.replace('-id-', encodeURIComponent(id));
        $('#globalModal').modal({
            show: 'true',
            remote: editUrl
        });
    }
</script>    
