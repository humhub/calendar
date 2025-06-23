<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav;

use humhub\modules\user\services\AuthClientService;
use Sabre\DAV\Auth\Backend\AbstractBasic;
use humhub\modules\user\models\User;
use humhub\modules\user\models\forms\Login;
use Yii;

class UserPassAuthBackend extends AbstractBasic
{
    /**
     * Validate user credentials.
     */
    protected function validateUserPass($username, $password)
    {
        return !Yii::$app->user->isGuest;
    }
}
