<?php

use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\widgets\EntryParticipants;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\file\widgets\FilePreview;
use yii\helpers\Html;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\CalendarEntry;

/* @var $calendarEntry CalendarEntry */
/* @var $stream boolean */
/* @var $collapse boolean */

$color = $calendarEntry->color ? $calendarEntry->color : $this->theme->variable('info');
?>

<div class="media event" style="" data-action-component="calendar.CalendarEntry" data-calendar-entry="<?= $calendarEntry->id ?>">
    <div style="padding-left:10px; border-left: 3px solid <?= $color ?>">
        <div class="media-body clearfix">
            <a href="<?= $calendarEntry->getUrl(); ?>" class="pull-left" style="margin-right: 10px">
                <i class="fa fa-calendar colorDefault" style="font-size: 35px;"></i>
            </a>
            <h4 class="media-heading">
                <a href="<?= $calendarEntry->getUrl(); ?>">
                    <b><?= Html::encode($calendarEntry->title); ?></b>
                </a>
            </h4>
            <h5>
                <?= $calendarEntry->getFormattedTime() ?>
                <?php if($calendarEntry->isAllDay()) : ?>
                        <small>(<?= Yii::t('CalendarModule.base', 'All Day') ?>)</small>
                <?php endif; ?>
            </h5>

        </div>
        <?php if (!empty($calendarEntry->description)) : ?>
            <hr>
            <div style="overflow:hidden" <?= ($collapse) ? 'data-ui-show-more' : '' ?> data-read-more-text="<?= Yii::t('CalendarModule.views_entry_view', "Read full description...") ?>" >
                <?= RichText::output($calendarEntry->description); ?>
            </div>
        <?php endif; ?>

        <?php if(RecurrenceHelper::isRecurrentInstance($calendarEntry)) :?>
            <hr style="border-style:dashed">
            <div style="margin:10px 0">
                <?= FilePreview::widget(['model' => $calendarEntry->getRecurrenceQuery()->getRecurrenceRoot()])?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($calendarEntry->participation->isEnabled()) : ?>
        <?php if($calendarEntry->participation->isShowParticipationInfo(Yii::$app->user->identity)) : ?>
            <br>
            <div class="row">
                <div class="col-md-12">
                    <div <?= ($collapse) ? 'data-ui-show-more' : '' ?> data-read-more-text="<?= Yii::t('CalendarModule.views_entry_view', "Read full participation info...") ?>">
                        <strong><i class="fa fa-info-circle"></i> <?= Yii::t('CalendarModule.views_entry_view', 'Additional information:') ?></strong>
                        <?= RichText::output( $calendarEntry->participant_info); ?>
                    </div>
                </div>
            </div>
            <br>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($calendarEntry->participation->isEnabled()) : ?>
        <?php if(!$calendarEntry->closed) : ?>
            <div class="row">
                <div class="col-md-12">
                    <?= EntryParticipants::widget(['calendarEntry' => $calendarEntry]); ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($calendarEntry->participation->canRespond(Yii::$app->user->identity)): ?>
       <div class="row" style="padding-top:10px">
            <div class="col-md-12">
              <?= EntryParticipants::participateButton($calendarEntry, CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED, Yii::t('CalendarModule.views_entry_view', "Attend")); ?>
              <?= EntryParticipants::participateButton($calendarEntry, CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE,    Yii::t('CalendarModule.views_entry_view', "Maybe")); ?>
              <?= EntryParticipants::participateButton($calendarEntry, CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED, Yii::t('CalendarModule.views_entry_view', "Decline")); ?>
            </div>
        </div>
    <?php endif; ?>
    
</div>
