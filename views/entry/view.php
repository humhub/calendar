<?php
use humhub\modules\content\assets\StreamAsset;
use humhub\modules\content\widgets\stream\StreamEntryWidget;

/* @var $entry \humhub\modules\calendar\models\CalendarEntry */
/* @var $collapse boolean */
?>
<?php StreamAsset::register($this); ?>

<div data-action-component="stream.SimpleStream">
    <?= StreamEntryWidget::renderStreamEntry($entry) ?>
</div>

