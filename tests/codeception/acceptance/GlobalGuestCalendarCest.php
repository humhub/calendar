<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use calendar\AcceptanceTester;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 25.07.2017
 * Time: 15:44
 */
class GlobalGuestCalendarCest
{
    public function testGlobalGuestViewProtectedSpace(AcceptanceTester $I)
    {
        $I->wantToTest('Guest access to calendar');
        $I->amAdmin();
        $I->allowGuestAccess();
        $I->enableModule(1, 'calendar');

        $I->amGoingTo('create a public event');

        $I->amOnSpace1('/calendar/view');
        $I->createEventToday('Public Event', 'Public Event Description', null, null, false);
        $I->jsClick('#calendarentryform-is_public');
        $I->click('Next', '#globalModal');

        $I->waitForText('Public Event', 10, '.fc-event-container');
        $I->click('Next', '#globalModal');
        $I->click('Save', '#globalModal');

        $I->wait(1);

        // Workaround regarding Webdriver click issues...
        $I->click('.filter-toggle-link');
        $I->waitForText('I\'m attending');
        $I->click('.calendar_filter_participate');

        $I->wait(1);

        $I->createEventToday('Private Event', 'Private Event Description');
        $I->waitForText('Next', 10, '#globalModal');
        $I->click('Next', '#globalModal');
        $I->click('Save', '#globalModal');
        $I->seeSuccess();

        $I->wait(1);

        $I->logout();
        $I->amOnRoute(['/calendar/global']);
        $I->wait(3);
        $I->dontSee('Public Event', '.fc-event-container');
        $I->dontSee('Private Event', '.fc-event-container');
    }

    public function testGlobalGuestViewPublicSpace(AcceptanceTester $I)
    {
        $I->wantToTest('Guest access to calendar');
        $I->amAdmin();
        $I->allowGuestAccess();

        $I->amUser1(true);
        $I->enableModule(2, 'calendar');

        $I->amGoingTo('create a public event');

        $I->amOnSpace2('/calendar/view');
        $I->createEventToday('Public Event', 'Public Event Description', null, null, false);
        $I->jsClick('#calendarentryform-is_public');
        $I->click('Next', '#globalModal');

        $I->waitForText('Public Event', 10, '.fc-event-container');
        $I->wait(1);
        $I->click('Next', '#globalModal');
        $I->click('Save', '#globalModal');

        $I->wait(1);

        // Workaround regarding Webdriver click issues...
        $I->click('.filter-toggle-link');
        $I->waitForText('I\'m attending');
        $I->click('.calendar_filter_participate');

        $I->wait(1);

        $I->createEventToday('Private Event', 'Private Event Description');
        $I->click('Next', '#globalModal');
        $I->click('Save', '#globalModal');

        $I->wait(1);

        $I->logout();
        $I->amOnRoute(['/calendar/global']);
        $I->waitForText('Public Event', 10, '.fc-event-container');
        $I->dontSee('Private Event', '.fc-event-container');

        $I->wantToTest('if a guest can opent the event');
        $I->click('.fc-event-container');

        $I->waitForText('Public Event', 10, '#globalModal');

        $I->dontSee('Edit', '#globalModal button');
        $I->dontSee('Attend', '#globalModal button');
        $I->dontSee('Maybe', '#globalModal button');
        $I->dontSee('Decline', '#globalModal button');

        $I->dontSeeInDropDown('#globalModal .dropdown-toggle', 'Edit');
        $I->dontSeeInDropDown('#globalModal .dropdown-toggle', 'Delete');
        $I->dontSeeInDropDown('#globalModal .dropdown-toggle', 'Cancel Event');
    }
}
