<?php
use yii\helpers\Html;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
/* @var $calendarEntry CalendarEntry */
?>

<?php if ($calendarEntry->isParticipationAllowed()) : ?>
    <strong><i class="fa fa-users"></i> <?= Yii::t('CalendarModule.widgets_views_participants', 'Participants:'); ?></strong><br>

    <?php $title = Yii::t('CalendarModule.widgets_views_participants', ":count attending", [':count' => $countAttending]); ?>
    <?php if ($countAttending > 0) : ?>
        <?= Html::a($title, $calendarEntry->content->container->createUrl('/calendar/entry/user-list', ['state' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED, 'id' => $calendarEntry->id]), ["class" => "tt colorSuccess", "title" => "", "data-target" => '#globalModal', "data-placement" => "top", "data-original-title" => ""]) ?>
    <?php else : ?>
        <?= $title; ?>
    <?php endif ?>

    <?php if($calendarEntry->allow_maybe) : ?>
         &middot;
        <?php $title = Yii::t('CalendarModule.widgets_views_participants', ":count maybe", [':count' => $countMaybe]); ?>
        <?php if ($countMaybe > 0) : ?>
            <?= Html::a($title, $calendarEntry->content->container->createUrl('/calendar/entry/user-list', ['state' => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE, 'id' => $calendarEntry->id]), ["class" => "tt colorInfo", "title" => "", "data-target" => '#globalModal', "data-placement" => "top", "data-original-title" => ""]); ?>
        <?php else : ?>
            <?= $title; ?>
        <?php endif ?>
    <?php endif ?>

    <?php if($calendarEntry->allow_decline) : ?>
         &middot;
        <?php $title = Yii::t('CalendarModule.widgets_views_participants', ":count declined", [':count' => $countDeclined]); ?>
        <?php if ($countDeclined > 0) : ?>
            <?= Html::a($title, $calendarEntry->content->container->createUrl('/calendar/entry/user-list', ['state' => CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED, 'id' => $calendarEntry->id]), ["class" => "tt colorWarning", "title" => "", "data-target" => '#globalModal', "data-placement" => "top", "data-original-title" => ""]); ?>
        <?php else : ?>
            <?= $title; ?>
        <?php endif ?>
    <?php endif ?>

    <br>
<?php endif; ?>