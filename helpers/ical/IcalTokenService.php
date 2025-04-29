<?php

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

    public function encrypt(string $guid): string
    {
        $issuedAt = time();
        $data = [
            'iat' => $issuedAt,
            'iss' => Yii::$app->settings->get('baseUrl'),
            'nbf' => $issuedAt,
            'guid' => $guid,
        ];

        $config = ExportSettings::instance();
        if (!empty($config->jwtExpire)) {
            $data['exp'] = $issuedAt + (int) $config->jwtExpire;
        }

        return JWT::encode($data, $config->jwtKey, 'HS256');
    }

    public function decrypt(string $token): ?string
    {
        try {
            $config = ExportSettings::instance();
            $validData = JWT::decode($token, new Key($config->jwtKey, 'HS256'));
            if (empty($validData->guid)) {
                throw new \RuntimeException();
            }

            return $validData->guid;
        } catch (Exception $e) {
            return null;
        }
    }
}
