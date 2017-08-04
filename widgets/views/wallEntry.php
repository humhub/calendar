<?php

use humhub\modules\calendar\widgets\EntryDate;
use humhub\modules\calendar\widgets\EntryParticipants;
use humhub\widgets\MarkdownView;
use yii\helpers\Html;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\CalendarEntry;

/* @var $calendarEntry CalendarEntry */
/* @var $stream boolean */
/* @var $collapse boolean */

$color = $calendarEntry->color ? $calendarEntry->color : $this->theme->variable('info');
?>

<div class="media event" data-action-component="calendar.CalendarEntry" data-calendar-entry="<?= $calendarEntry->id ?>">
    <div class="media-body" style="padding-left:10px; border-left: 3px solid <?= $color ?>">
        <div class="clearfix">
            <a href="<?= $calendarEntry->getUrl(); ?>" class="pull-left" style="margin-right: 10px">
                <i class="fa fa-calendar colorDefault" style="font-size: 35px;"></i>
            </a>
            <h4 class="media-heading">
                <a href="<?= $calendarEntry->getUrl(); ?>">
                    <b><?= Html::encode($calendarEntry->title); ?></b>
                </a>
            </h4>
            <h5>
                <?= EntryDate::widget(['entry' => $calendarEntry]); ?>
            </h5>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?php if (!empty($calendarEntry->description)) : ?>
                    <div <?= ($collapse) ? 'data-ui-show-more' : '' ?> data-read-more-text="<?= Yii::t('CalendarModule.views_entry_view', 'Read full description...') ?>" style="overflow:hidden">
                        <?= MarkdownView::widget(['markdown' => $calendarEntry->description]); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($calendarEntry->isParticipationAllowed()) : ?>
        <?php if (!$calendarEntry->closed) : ?>
            <br>
            <div class="row">
                <div class="col-md-12">
                    <?= EntryParticipants::widget(['calendarEntry' => $calendarEntry]); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($calendarEntry->participant_info) && $calendarEntry->isParticipant()) : ?>
            <br />
            <div class="row">
                <div class="col-md-12">
                    <div <?= ($collapse) ? 'data-ui-show-more' : '' ?> data-read-more-text="<?= Yii::t('CalendarModule.views_entry_view', 'Read full participation info...') ?>">
                        <strong><i class="fa fa-info-circle"></i> <?= Yii::t('CalendarModule.views_entry_view', 'Participant information:') ?></strong>
                        <?= MarkdownView::widget(['markdown' => $calendarEntry->participant_info]); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($calendarEntry->canRespond()) : ?>
       <div class="row" style="padding-top:10px">
            <div class="col-md-12">

                <?php
                $cssState = ['', '', '', ''];
                $cssState[$participantSate] = 'disabled';
                ?>
                <button data-action-click="calendar.respond" class="btn btn-default btn-sm" data-ui-loader <?= $cssState[CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED] ?>
                        data-action-url="<?= $contentContainer->createUrl('/calendar/entry/respond', ['type' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED, 'id' => $calendarEntry->id]) ?>">
                    <?= $participantSate === CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED ? '<i class="fa fa-check"></i>' : '' ?>
                    <?= Yii::t('CalendarModule.views_entry_view', 'Attend') ?>
                </button>

                <?php if ($calendarEntry->allow_maybe) : ?>
                    <button data-action-click="calendar.respond" class="btn btn-default btn-sm" data-ui-loader  <?= $cssState[CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE] ?>
                            data-action-url="<?= $contentContainer->createUrl('/calendar/entry/respond', ['type' => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE, 'id' => $calendarEntry->id]) ?>">
                        <?= $participantSate === CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE ? '<i class="fa fa-check"></i>' : ''?>
                        <?= Yii::t('CalendarModule.views_entry_view', 'Maybe') ?>
                    </button>
                <?php endif; ?>

                <?php if ($calendarEntry->allow_decline) : ?>
                    <button data-action-click="calendar.respond" class="btn btn-default btn-sm" data-ui-loader  <?= $cssState[CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED] ?>
                            data-action-url="<?= $contentContainer->createUrl('/calendar/entry/respond', ['type' => CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED, 'id' => $calendarEntry->id]) ?>">
                        <?= $participantSate === CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED ? '<i class="fa fa-check"></i>' : ''?>
                        <?= Yii::t('CalendarModule.views_entry_view', 'Decline') ?>
                    </button>
                <?php endif;?>

            </div>
        </div>
    <?php endif; ?>
</div>

