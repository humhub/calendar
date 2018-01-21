<?php

use yii\helpers\Html;

echo Yii::t('CalendarModule.views_activities_EntryResponse', '%displayName% maybe attends to %contentTitle%.', [
    '%displayName%' => '<strong>' . Html::encode($originator->displayName) . '</strong>',
    '%contentTitle%' => $this->context->getContentInfo($source)
]);
