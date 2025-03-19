<?php

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
        $login = new Login();
        $login->username = $username;
        $login->password = $password;

        if ($login->validate()) {
            $authClientService = new AuthClientService($login->authClient);
            $authClientService->autoMapToExistingUser();
            Yii::$app->user->login($authClientService->getUser());

            return true;
        }
        return false;
    }
}
