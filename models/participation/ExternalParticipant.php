<?php

namespace humhub\modules\calendar\models\participation;

use yii\base\BaseObject;

class ExternalParticipant extends BaseObject
{
    public string $email;

    public int $status;
}