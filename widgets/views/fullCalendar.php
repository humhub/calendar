<?php

use yii\helpers\Html;

\humhub\modules\calendar\Assets::register($this);

$this->registerJsVar('fullCalendarCanWrite', $canWrite ? 'true' : 'false');
$this->registerJsVar('fullCalendarTimezone', date_default_timezone_get());
$this->registerJsVar('fullCalendarLanguage', Yii::$app->language);
$this->registerJsVar('fullCalendarLoadUrl', $loadUrl);
$this->registerJsVar('fullCalendarCreateUrl', $createUrl);
$this->registerJsVar('fullCalendarSelectors', Html::encode(join(",", $selectors)));
$this->registerJsVar('fullCalendarFilters', Html::encode(join(",", $filters)));
?>

<div id='calendar' ></div>
<div id='loading' class="loader"><div class="sk-spinner sk-spinner-three-bounce"><div class="sk-bounce1"></div><div class="sk-bounce2"></div><div class="sk-bounce3"></div></div></div>
