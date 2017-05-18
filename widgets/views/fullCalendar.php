<?php

use yii\helpers\Html;

\humhub\modules\calendar\assets\Assets::register($this);

$this->registerJsVar('fullCalendarCanWrite', $canWrite ? 'true' : 'false');
$this->registerJsVar('fullCalendarTimezone', date_default_timezone_get());
$this->registerJsVar('fullCalendarLanguage', Yii::$app->language);
$this->registerJsVar('fullCalendarLoadUrl', $loadUrl);
$this->registerJsVar('fullCalendarCreateUrl', $createUrl);
$this->registerJsVar('fullCalendarSelectors', Html::encode(join(",", $selectors)));
$this->registerJsVar('fullCalendarFilters', Html::encode(join(",", $filters)));
?>

<div id='calendar' ></div>
