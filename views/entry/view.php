<?php /* @var $entry \humhub\modules\calendar\models\CalendarEntry */?>
<?php \humhub\modules\stream\assets\StreamAsset::register($this); ?>

<div data-action-component="stream.SimpleStream">
    <?= humhub\modules\stream\actions\Stream::renderEntry($entry)?>
</div>

