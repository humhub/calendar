<?php

namespace humhub\modules\calendar\helpers\dav\event;

use humhub\modules\calendar\helpers\dav\EventProperties;
use humhub\modules\calendar\helpers\dav\SyncService;
use humhub\modules\calendar\interfaces\event\legacy\CalendarEventIFWrapper;
use yii\base\Event;

class UpdateObjectEvent extends Event
{
    public $name = SyncService::EVENT_UPDATE_OBJECT;

    public ?CalendarEventIFWrapper $object;

    public ?EventProperties $properties;
}
