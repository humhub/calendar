<?php

namespace humhub\modules\calendar\helpers\dav\event;

use humhub\modules\calendar\helpers\dav\SyncService;
use yii\base\Event;

class GetObjectEvent extends Event
{
    public $name = SyncService::EVENT_GET_OBJECT;

    public $objectId;

    public $object;
}