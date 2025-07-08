<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav;

use humhub\modules\calendar\helpers\AuthTokenService;
use yii\filters\auth\HttpBasicAuth;
use humhub\modules\user\services\AuthClientService;
use humhub\modules\user\models\forms\Login;
use humhub\modules\user\models\User;

class CalDavAuth extends HttpBasicAuth
{
    public $auth;

    public function init()
    {
        parent::init();

        $this->auth = [$this, 'auth'];
    }

    public function auth($username, $password)
    {
        $login = new Login();
        $login->username = $username;
        $login->password = $password;

        if ($login->validate()) {
            $authClientService = new AuthClientService($login->authClient);
            $authClientService->autoMapToExistingUser();
            $user = $authClientService->getUser();

            if ($user->isActive()) {
                return $user;
            }
        }

        if (!empty($data = AuthTokenService::instance()->calDavDecrypt($password))) {
            [$id, $username] = $data;

            return User::find()->active()->andWhere(['id' => $id, 'username' => $username])->one();
        }

        return null;
    }
}
