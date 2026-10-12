<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace calendar\functional;

use calendar\FunctionalTester;
use Yii;

class CalDavCest
{
    /**
     * CalDAV authenticates statelessly per request, so the controller disables the session.
     * This marks the request as an API request for the core user gates (2FA, terms, ...),
     * which only apply to session-authenticated requests — keeping the sync reachable for
     * users who would otherwise be intercepted (see core docs/develop/user-gates.md).
     */
    public function testCalDavRequestDisablesSession(FunctionalTester $I)
    {
        $I->wantTo('ensure CalDAV requests run without a session so the user gates do not intercept them');

        $I->stopFollowingRedirects();
        $I->amOnRoute('/calendar/cal-dav/well-known');

        $I->assertFalse(Yii::$app->user->enableSession, 'CalDAV controller must disable the session');
    }
}
