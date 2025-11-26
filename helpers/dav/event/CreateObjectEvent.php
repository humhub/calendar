<?php

namespace humhub\modules\calendar\helpers\dav\event;

use humhub\modules\calendar\helpers\dav\SyncService;
use yii\base\Event;

class CreateObjectEvent extends Event
{
    public $name = SyncService::EVENT_CREATE_OBJECT;
}