<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\ical;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use humhub\modules\calendar\models\ExportSettings;
use humhub\modules\rest\models\JwtAuthForm;
use Yii;
use yii\base\BaseObject;
use yii\base\StaticInstanceInterface;
use yii\base\StaticInstanceTrait;

class IcalTokenService extends BaseObject implements StaticInstanceInterface
{
    use StaticInstanceTrait;

    public function encrypt(int $uid, string $guid): string
    {
        $issuedAt = time();
        $data = [
            'iat' => $issuedAt,
            'iss' => Yii::$app->settings->get('baseUrl'),
            'nbf' => $issuedAt,
            'uid' => $uid,
            'guid' => $guid,
        ];

        $config = ExportSettings::instance();
        if (!empty($config->jwtExpire)) {
            $data['exp'] = $issuedAt + (int) $config->jwtExpire;
        }

        return JWT::encode($data, $config->jwtKey, 'HS256');
    }

    public function decrypt(string $token): ?array
    {
        try {
            $config = ExportSettings::instance();
            $validData = JWT::decode($token, new Key($config->jwtKey, 'HS256'));
            if (empty($validData->uid) || empty($validData->guid)) {
                throw new \RuntimeException();
            }

            return [$validData->uid, $validData->guid];
        } catch (Exception $e) {
            return null;
        }
    }
}
