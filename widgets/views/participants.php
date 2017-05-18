<?php
use yii\helpers\Html;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
?>

<?php if ($calendarEntry->participation_mode != CalendarEntry::PARTICIPATION_MODE_NONE) : ?>
    <strong><?php echo Yii::t('CalendarModule.widgets_views_participants', 'Participants:'); ?></strong><br>
    <?php
    $title = Yii::t('CalendarModule.widgets_views_participants', ":count attending", array(':count' => $countAttending));
    if ($countAttending > 0) {
        echo Html::a($title, $calendarEntry->content->container->createUrl('/calendar/entry/user-list', array('state' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED, 'id' => $calendarEntry->id)), array("class" => "tt colorSuccess", "title" => "", "data-target" => '#globalModal', "data-placement" => "top", "data-original-title" => ""));
    } else {
        echo $title;
    }
    echo " &middot; ";
    $title = Yii::t('CalendarModule.widgets_views_participants', ":count maybe", array(':count' => $countMaybe));
    if ($countMaybe > 0) {
        echo Html::a($title, $calendarEntry->content->container->createUrl('/calendar/entry/user-list', array('state' => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE, 'id' => $calendarEntry->id)), array("class" => "tt colorInfo", "title" => "", "data-target" => '#globalModal', "data-placement" => "top", "data-original-title" => ""));
    } else {
        echo $title;
    }
    echo " &middot; ";
    $title = Yii::t('CalendarModule.widgets_views_participants', ":count declined", array(':count' => $countDeclined));
    if ($countDeclined > 0) {
        echo Html::a($title, $calendarEntry->content->container->createUrl('/calendar/entry/user-list', array('state' => CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED, 'id' => $calendarEntry->id)), array("class" => "tt colorWarning", "title" => "", "data-target" => '#globalModal', "data-placement" => "top", "data-original-title" => ""));
    } else {
        echo $title;
    }
    ?>
    <br>
<?php endif; ?>