<?php

use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\modules\calendar\models\CalendarEntry;
use yii\helpers\Html;

/* @var $source CalendarEntry */
/* @var $originator \humhub\modules\user\models\User */

$formatter = new CalendarDateFormatter(['calendarItem' => $source]);

echo Yii::t('CalendarModule.views_activities_EntryResponse', '%displayName% might be attending %contentTitle%.', [
    '%displayName%' => '<strong>' . Html::encode($originator->displayName) . '</strong>',
    '%contentTitle%' => $this->context->getContentInfo($source).' on '.$formatter->getFormattedTime()
]);
?>
