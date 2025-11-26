<?php

namespace humhub\modules\calendar\helpers\dav\event;

use humhub\modules\calendar\helpers\dav\SyncService;
use yii\base\Event;

class UpdateObjectEvent extends Event
{
    public $name = SyncService::EVENT_UPDATE_OBJECT;
}