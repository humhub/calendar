<?php

use humhub\libs\Html;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\ui\view\components\View;

/**
 * @var $this View
 * @var $calendarEntry CalendarEventIF
 */
?>

<span class="calendar-entry-download-link">
    <?= Html::a(Yii::t('CalendarModule.base', 'Download ICS'), Url::toEntryDownloadICS($calendarEntry), ['target' => '_blank']) ?>
</span>
