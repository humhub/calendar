<?php

use humhub\modules\calendar\helpers\Url;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\ui\view\components\View;
use humhub\widgets\ModalButton;

/**
 * @var $this View
 * @var $entry ContentActiveRecord
 */
?>

<span class="calendar-entry-reminder-link">
    <?= ModalButton::asLink(Yii::t('CalendarModule.base', 'Set reminder'))->load(Url::toUserLevelReminderConfig($entry))->loader(true) ?>
</span>
