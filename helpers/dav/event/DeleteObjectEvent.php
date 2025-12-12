<?php

namespace humhub\modules\calendar\helpers\dav\event;

use humhub\modules\calendar\helpers\dav\SyncService;
use humhub\modules\calendar\interfaces\event\legacy\CalendarEventIFWrapper;
use yii\base\Event;

class DeleteObjectEvent extends Event
{
    public $name = SyncService::EVENT_DELETE_OBJECT;

    public ?CalendarEventIFWrapper $object = null;
}
