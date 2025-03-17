<?php

namespace humhub\modules\calendar\helpers\dav;


use Sabre\DAV\Auth\Backend\AbstractBasic;
use Yii;
use humhub\modules\user\models\User;
use yii\web\ForbiddenHttpException;

class TokenAuthBackend extends AbstractBasic
{
    protected function validateUserPass($username, $password)
    {
        return true;
    }
}