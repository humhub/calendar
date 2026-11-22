<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\tests\codeception\unit\caldav;

use calendar\CalendarUnitTest;
use humhub\modules\calendar\helpers\AuthTokenService;
use humhub\modules\calendar\helpers\dav\CalDavAuth;
use humhub\modules\calendar\models\ExportSettings;
use humhub\modules\calendar\Module;
use humhub\modules\oauth\components\DeviceConnectionRegistry;
use humhub\modules\oauth\models\Grant;
use humhub\modules\oauth\Module as OAuthModule;
use humhub\modules\oauth\services\GrantService;
use humhub\modules\oauth\services\PersonalAccessTokenService;
use humhub\modules\user\models\Password;
use humhub\modules\user\models\User;
use Yii;

/**
 * Signing in a calendar app over CalDAV: with a password of the device connection (a personal
 * access token), with an earlier CalDAV password, which becomes such a token, or still with the
 * password of the account.
 */
class CalDavAuthTest extends CalendarUnitTest
{
    private User $user;

    public function _before()
    {
        parent::_before();
        $this->user = User::findOne(['id' => 2]);
    }

    public function _after()
    {
        unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        OAuthModule::instance()->activeGrant = null;
        OAuthModule::instance()->settings->delete('allowPersonalAccessTokens');
        $this->module()->settings->delete('enableCalDav');
        $this->module()->settings->user($this->user)->delete(Module::CALDAV_LEGACY_CUTOFF);
        Grant::deleteAll();
        parent::_after();
    }

    private function module(): Module
    {
        return Yii::$app->getModule('calendar');
    }

    private function signIn(string $password, string $method = 'PROPFIND'): ?User
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['PHP_AUTH_USER'] = $this->user->username;
        $_SERVER['PHP_AUTH_PW'] = $password;
        OAuthModule::instance()->activeGrant = null;

        return (new CalDavAuth())->auth($this->user->username, $password);
    }

    private function token(array $scopes = [Module::SCOPE_READ, Module::SCOPE_WRITE]): string
    {
        return (new PersonalAccessTokenService())->issue($this->user, Module::CALDAV_CONNECTION, 'CalDAV', $scopes)[0];
    }

    public function testCalendarAppsAreOfferedAConnection()
    {
        $this->assertNull(DeviceConnectionRegistry::find($this->user, Module::CALDAV_CONNECTION), 'CalDAV needs pretty URLs');

        Yii::$app->urlManager->enablePrettyUrl = true;
        try {
            $connection = DeviceConnectionRegistry::find($this->user, Module::CALDAV_CONNECTION);
        } finally {
            Yii::$app->urlManager->enablePrettyUrl = false;
        }

        $this->assertNotNull($connection);
        $this->assertSame([Module::SCOPE_READ, Module::SCOPE_WRITE], $connection->scopes);
        $this->assertContains($this->user->username, $connection->fields);
    }

    public function testThePasswordOfTheConnectionSignsIn()
    {
        $this->assertSame($this->user->id, $this->signIn($this->token())?->id);
        $this->assertNotNull(OAuthModule::instance()->activeGrant);
        $this->assertNull($this->signIn('hhpat_' . str_repeat('A', 38)));
    }

    public function testAReadingTokenDoesNotWrite()
    {
        $token = $this->token([Module::SCOPE_READ]);

        $this->assertNotNull($this->signIn($token, 'REPORT'));
        $this->assertNull($this->signIn($token, 'PUT'));
    }

    public function testAnEarlierPasswordBecomesATokenOnFirstUse()
    {
        $earlier = AuthTokenService::instance()->calDavEncrypt($this->user);

        $this->assertSame($this->user->id, $this->signIn($earlier)?->id);
        $this->assertSame($this->user->id, $this->signIn($earlier, 'PUT')?->id);

        $grants = Grant::findAll(['user_id' => $this->user->id, 'issuer' => Module::CALDAV_CONNECTION]);
        $this->assertCount(1, $grants);
        $this->assertNull($grants[0]->token_hint);
        $this->assertSame([Module::SCOPE_READ, Module::SCOPE_WRITE], $grants[0]->getScopeIds());
    }

    public function testRevokingAConvertedPasswordEndsTheEarlierPasswords()
    {
        $first = AuthTokenService::instance()->calDavEncrypt($this->user);
        $second = AuthTokenService::instance()->calDavEncrypt($this->user);
        $this->assertNotNull($this->signIn($first));

        (new GrantService())->revoke(Grant::findOne(['user_id' => $this->user->id, 'issuer' => Module::CALDAV_CONNECTION]));

        $this->assertNull($this->signIn($first));
        $this->assertNull($this->signIn($second));
        // The passwords of the dialog are not affected
        $this->assertNotNull($this->signIn($this->token()));
    }

    public function testTheAccountPasswordStillSignsIn()
    {
        $password = new Password(['user_id' => $this->user->id]);
        $password->setPassword('An-account-password-1');
        $this->assertTrue($password->save());

        $this->assertSame($this->user->id, $this->signIn('An-account-password-1')?->id);
    }

    public function testNoOneSignsInWhileCalDavIsSwitchedOff()
    {
        $token = $this->token();

        $this->module()->settings->set('enableCalDav', false);
        $this->assertNull($this->signIn($token));
        $this->assertFalse($this->module()->isCalDavAvailable());
    }

    public function testTheSwitchNeedsPersonalAccessTokens()
    {
        $this->assertTrue((new ExportSettings())->canEnableCalDav());
        $this->assertTrue((bool)(new ExportSettings())->enableCalDav);

        OAuthModule::instance()->settings->set('allowPersonalAccessTokens', false);
        $this->assertFalse((new ExportSettings())->canEnableCalDav());
        $this->assertFalse($this->module()->isCalDavAvailable());
    }
}
