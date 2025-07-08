<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use humhub\modules\calendar\models\ExportSettings;
use humhub\modules\rest\models\JwtAuthForm;
use humhub\modules\user\models\User;
use Yii;
use yii\base\BaseObject;
use yii\base\StaticInstanceInterface;
use yii\base\StaticInstanceTrait;

class AuthTokenService extends BaseObject implements StaticInstanceInterface
{
    use StaticInstanceTrait;

    public function iCalEncrypt(int $uid, string $guid, bool $global): string
    {
        $issuedAt = time();
        $data = [
            'iat' => $issuedAt,
            'iss' => Yii::$app->settings->get('baseUrl'),
            'nbf' => $issuedAt,
            'uid' => $uid,
            'guid' => $guid,
            'global' => $global,
        ];

        $config = ExportSettings::instance();
        if (!empty($config->jwtExpire)) {
            $data['exp'] = $issuedAt + (int) $config->jwtExpire;
        }

        return JWT::encode($data, $config->jwtKey, 'HS256');
    }

    public function iCalDecrypt(string $token): ?array
    {
        try {
            $config = ExportSettings::instance();
            $validData = JWT::decode($token, new Key($config->jwtKey, 'HS256'));
            if (empty($validData->uid) || empty($validData->guid) || !isset($validData->global)) {
                throw new \RuntimeException();
            }

            return [$validData->uid, $validData->guid, $validData->global];
        } catch (Exception $e) {
            return null;
        }
    }

    public function calDavEncrypt(User $user)
    {
        $issuedAt = time();
        $data = [
            'iat' => $issuedAt,
            'iss' => Yii::$app->settings->get('baseUrl'),
            'nbf' => $issuedAt,
            'uid' => $user->id,
            'username' => $user->username,
        ];

        $config = ExportSettings::instance();
        if (!empty($config->jwtExpire)) {
            $data['exp'] = $issuedAt + (int) $config->jwtExpire;
        }

        return JWT::encode($data, $config->jwtKey, 'HS256');
    }

    public function calDavDecrypt(string $token)
    {
        try {
            $config = ExportSettings::instance();
            $validData = JWT::decode($token, new Key($config->jwtKey, 'HS256'));
            if (empty($validData->uid) || empty($validData->username)) {
                throw new \RuntimeException();
            }

            return [$validData->uid, $validData->username];
        } catch (Exception $e) {
            return null;
        }
    }
}
