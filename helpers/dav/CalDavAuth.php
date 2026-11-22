<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav;

use humhub\modules\calendar\helpers\AuthTokenService;
use humhub\modules\calendar\Module;
use humhub\modules\dav\components\TokenBasicAuth;
use humhub\modules\oauth\components\ScopeRegistry;
use humhub\modules\oauth\models\Grant;
use humhub\modules\oauth\Module as OAuthModule;
use humhub\modules\oauth\services\GrantService;
use humhub\modules\oauth\services\PersonalAccessTokenService;
use humhub\modules\user\models\forms\Login;
use humhub\modules\user\models\User;
use humhub\modules\user\services\AuthClientService;
use Yii;
use yii\db\IntegrityException;
use yii\filters\auth\HttpBasicAuth;

/**
 * HTTP Basic authentication of calendar apps (CalDAV), while CalDAV is available
 * (`Module::isCalDavAvailable()`), with the username or e-mail address and
 *
 * - a password of the device connection: a personal access token with the calendar's scopes,
 *   reading with `calendar:read`, writing with `calendar:write`;
 * - a CalDAV password of earlier versions (a signed token of the module): converted into such
 *   a token on first use, with its former lifetime, unless the user revoked a converted one
 *   since ([[Module::CALDAV_LEGACY_CUTOFF]]);
 * - the password of the account, deprecated.
 */
class CalDavAuth extends HttpBasicAuth
{
    /**
     * The methods that only read; every other one writes.
     */
    private const READ_METHODS = ['GET', 'HEAD', 'OPTIONS', 'PROPFIND', 'REPORT'];

    public $auth;

    public function init()
    {
        parent::init();

        $this->auth = [$this, 'auth'];
    }

    public function auth($username, $password)
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('calendar');
        if (!$module->isCalDavAvailable() || !is_string($password) || $password === '') {
            return null;
        }

        $grant = (new TokenBasicAuth())->authenticate(Yii::$app->request, Module::CALDAV_CONNECTION)
            ?? $this->convertEarlierPassword($module, $password);
        if ($grant !== null) {
            return $this->signInWith($grant);
        }

        // Deprecated: the password of the account
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

        return null;
    }

    /**
     * The user of the grant, when it has the scope of the request's method. The method is the
     * one Sabre runs, not an override.
     */
    private function signInWith(Grant $grant): ?User
    {
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $required = in_array($method, self::READ_METHODS, true) ? Module::SCOPE_READ : Module::SCOPE_WRITE;

        $service = new GrantService();
        $scopes = $service->getEffectiveScopes($grant);
        if (!in_array($required, $scopes, true) && !in_array(ScopeRegistry::FULL, $scopes, true)) {
            return null;
        }

        OAuthModule::instance()->activeGrant = $grant;
        $service->touch($grant);

        return $grant->user;
    }

    /**
     * The token of a CalDAV password of earlier versions, converted on its first use.
     */
    private function convertEarlierPassword(Module $module, string $password): ?Grant
    {
        $data = AuthTokenService::instance()->calDavDecrypt($password);
        if ($data === null) {
            return null;
        }

        [$userId, $username, $issuedAt, $expiresAt] = $data;
        $user = User::find()->active()->andWhere(['id' => $userId, 'username' => $username])->one();
        if ($user === null) {
            return null;
        }

        $cutoff = $module->settings->user($user)->get(Module::CALDAV_LEGACY_CUTOFF);
        if ($cutoff !== null && $issuedAt <= (int)$cutoff) {
            return null;
        }

        $service = new PersonalAccessTokenService();
        try {
            $grant = $service->import($user, Module::CALDAV_CONNECTION, Yii::t('CalendarModule.export', 'CalDAV (earlier password)'), [Module::SCOPE_READ, Module::SCOPE_WRITE], $password, $expiresAt);
        } catch (IntegrityException) {
            // Converted by a request in parallel, or converted before and no longer usable
            $grant = $service->findImported($password, Module::CALDAV_CONNECTION);
        }

        return $grant !== null && (new GrantService())->isUsable($grant) ? $grant : null;
    }
}
