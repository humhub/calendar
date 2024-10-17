<?php

use humhub\widgets\ModalButton;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;

/* @var $calendarEntry CalendarEntry */
/* @var $countAttending integer */
/* @var $countMaybe integer */
/* @var $countDeclined integer */
/* @var $countInvited integer */
?>

<?php if ($calendarEntry->participation->isEnabled()) : ?>
    <h5 style="margin:0 0 5px;font-weight:600"><?= Yii::t('CalendarModule.views', 'Participants') ?></h5>

    <?php $title = Yii::t('CalendarModule.views', ':count Attending', [':count' => $countAttending]); ?>
    <?php if ($countAttending > 0) : ?>
        <?= ModalButton::instance($title)
            ->load(Url::toParticipationUserList($calendarEntry, CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED))
            ->action('calendar.editModal')
            ->link()?>
    <?php else : ?>
        <?= $title; ?>
    <?php endif ?>

    <?php if($calendarEntry->allow_maybe) : ?>
         &middot;
        <?php $title = Yii::t('CalendarModule.views', ':count Undecided', [':count' => $countMaybe]); ?>
        <?php if ($countMaybe > 0) : ?>
            <?= ModalButton::instance($title)
                ->load(Url::toParticipationUserList($calendarEntry, CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE))
                ->action('calendar.editModal')
                ->link() ?>
        <?php else : ?>
            <?= $title; ?>
        <?php endif ?>
    <?php endif ?>

    <?php if($calendarEntry->allow_decline) : ?>
         &middot;
        <?php $title = Yii::t('CalendarModule.views', ':count Declined', [':count' => $countDeclined]); ?>
        <?php if ($countDeclined > 0) : ?>
            <?= ModalButton::instance($title)
                ->load(Url::toParticipationUserList($calendarEntry, CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED))
                ->action('calendar.editModal')
                ->link() ?>
        <?php else : ?>
            <?= $title; ?>
        <?php endif ?>
    <?php endif ?>

    &middot; <?php $title = Yii::t('CalendarModule.views', ':count Invited', [':count' => $countInvited]); ?>
    <?php if ($countInvited > 0) : ?>
        <?= ModalButton::instance($title)
            ->load(Url::toParticipationUserList($calendarEntry, CalendarEntryParticipant::PARTICIPATION_STATE_INVITED))
            ->action('calendar.editModal')
            ->link() ?>
    <?php else : ?>
        <?= $title; ?>
    <?php endif ?>

    <br>
<?php endif; ?>