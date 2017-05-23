<?php

use yii\helpers\Html;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\CalendarEntry;

$color = $calendarEntry->color ? $calendarEntry->color : $this->theme->variable('info');
?>

<div class="media event">
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
                <?= humhub\modules\calendar\widgets\EntryDate::widget(['entry' => $calendarEntry]); ?>
            </h5>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?php if ($calendarEntry->description != ""): ?>
                    <?= nl2br(Html::encode($calendarEntry->description)); ?>
                    <br>
                <?php endif; ?>
                <?php if ($calendarEntry->participation_mode != CalendarEntry::PARTICIPATION_MODE_NONE) : ?>
                    <br>
                    <?= \humhub\modules\calendar\widgets\EntryParticipants::widget(['calendarEntry' => $calendarEntry]); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($calendarEntry->canRespond()): ?>
       <div class="row" style="padding-top:10px">
            <div class="col-md-12">

                    <?php
                    $cssState = ["", "", "", ""];
                    $cssState[$participantSate] = "disabled";
                    ?>
                    <button data-action-click="calendar.respond" class="btn btn-default btn-sm" data-ui-loader <?= $cssState[CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED] ?>
                            data-action-url="<?= $contentContainer->createUrl('/calendar/entry/respond', ['type' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED, 'id' => $calendarEntry->id]) ?>">
                        <?= $participantSate === CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED ? '<i class="fa fa-check"></i>' : '' ?>
                        <?= Yii::t('CalendarModule.views_entry_view', "Attend") ?>
                    </button>
                    <button data-action-click="calendar.respond" class="btn btn-default btn-sm" data-ui-loader  <?= $cssState[CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE] ?>
                            data-action-url="<?= $contentContainer->createUrl('/calendar/entry/respond', ['type' => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE, 'id' => $calendarEntry->id]) ?>">
                        <?= $participantSate === CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE ? '<i class="fa fa-check"></i>' : ''?>
                        <?= Yii::t('CalendarModule.views_entry_view', "Maybe") ?>
                    </button>
                    <button data-action-click="calendar.respond" class="btn btn-default btn-sm" data-ui-loader  <?= $cssState[CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED] ?>
                            data-action-url="<?= $contentContainer->createUrl('/calendar/entry/respond', ['type' => CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED, 'id' => $calendarEntry->id]) ?>">
                        <?= $participantSate === CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED ? '<i class="fa fa-check"></i>' : ''?>
                        <?= Yii::t('CalendarModule.views_entry_view', "Decline") ?>
                    </button>

            </div>
        </div>
    <?php endif; ?>
</div>

