<?php

use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\modules\calendar\models\CalendarEntry;
use yii\helpers\Html;

/* @var $source CalendarEntry */
/* @var $originator \humhub\modules\user\models\User */

$formatter = new CalendarDateFormatter(['calendarItem' => $source]);
?>

<?= Yii::t('CalendarModule.views', '{displayName} is attending {contentTitle} on {dateTime}.', [
    'displayName' => '<strong>' . Html::encode($originator->displayName) . '</strong>',
    'contentTitle' => $this->context->getContentInfo($source),
    'dateTime' => $formatter->getFormattedTime(),
]) ?>
