<?php
use humhub\modules\content\widgets\PinLink;
use humhub\modules\stream\assets\StreamAsset;
use humhub\modules\stream\actions\Stream;

/* @var $entry \humhub\modules\calendar\models\CalendarEntry */
/* @var $collapse boolean */
?>
<?php StreamAsset::register($this); ?>

<div data-action-component="stream.SimpleStream">
    <?= Stream::renderEntry($entry, [
            'stream' => $stream,
            'controlsOptions' => [
                    'prevent' => [PinLink::class]
            ]
    ])?>
</div>

