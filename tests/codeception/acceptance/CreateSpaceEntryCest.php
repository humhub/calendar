<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace calendar\acceptance;

use calendar\AcceptanceTester;

class CreateSpaceEntryCest
{
    
    public function testInstallAndCreatEntry(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->wantToTest('the creation of a calendar entry');
        $I->amGoingTo('install the calendar module for space 1');
        $I->enableModule(1, 'calendar');

        $I->amOnSpace1();
        $I->expectTo('see calendar entry in the space nav');
        $I->waitForText('Calendar', null, '.layout-nav-container');

        $I->amGoingTo('create a new entry');
        $I->click('Calendar', '.layout-nav-container');
        $I->waitForElementVisible('.fc-today');
        $I->click('.fc-today');
        $I->waitForText('Create event', null, '#globalModal');

        $I->fillField('CalendarEntry[title]', 'My Test Entry');
        $I->fillField('#calendarentry-description .humhub-ui-richtext', 'My Test Entry Description');

        $I->wantToTest('the hide/show functionality for time values (all day selection)');
        $I->seeElement('#calendarentryform-start_time:disabled');
        $I->seeElement('#calendarentryform-end_time:disabled');
        $I->dontSeeInField('#calendarentryform-start_time', '12:00 AM');
        $I->dontSeeInField('#calendarentryform-end_time', '11:59 PM');

        $I->click('[for="calendarentry-all_day"]');

        $I->wait(1);
        $I->seeElement('#calendarentryform-start_time:not(:disabled)');
        $I->seeElement('#calendarentryform-end_time:not(:disabled)');
        $I->seeInField('#calendarentryform-start_time', '12:00 AM');
        $I->seeInField('#calendarentryform-end_time', '11:59 PM');

        $I->click('[for="calendarentry-all_day"]');
        $I->wait(1);

        $I->seeElement('#calendarentryform-start_time:disabled');
        $I->seeElement('#calendarentryform-end_time:disabled');

        $I->dontSeeInField('#calendarentryform-start_time', '12:00 AM');
        $I->dontSeeInField('#calendarentryform-end_time', '11:59 PM');


        $I->amGoingTo('Save my new calendar entry');
        $I->click('Save', '#globalModal');
        $I->expectTo('see my event loaded into my modal');
        $I->waitForText('My Test Entry',null, '#globalModal');
        $I->waitForText('Close',null, '#globalModal');

        $I->click('Close', '#globalModal');

        $I->wait(1);

        $I->wantToTest('if my new entry was loaded into my calendar');
        $I->waitForElementVisible('.fc-event-container');
        $I->expectTo('see my entry title in my calendar');
        $I->see('My Test Entry', '.fc-event-container');

        $I->amOnSpace1();
        $I->waitForText('My Test Entry', null, '[data-stream-entry]');
        $I->see('My Test Entry', '[data-stream-entry]');
    }

    public function testAddAll(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->wantToTest('The auto add of all space members to a new event');

        $I->enableModule(1, 'calendar');
        $I->amOnSpace(1, '/calendar/view/index');

        $I->waitForElementVisible('.fc-today');
        $I->click('.fc-today');
        $I->waitForText('Create event');

        $I->fillField('CalendarEntry[title]', 'New Test Event');

        $I->click('Participation', '#globalModal');
        $I->waitForElementVisible('[for="calendarentryform-forcejoin"]', null, '#globalModal');
        $I->click('[for="calendarentryform-forcejoin"]');
        $I->click('Save', '#globalModal');

        $I->expect('All space members to be attending');
        $I->waitForText('2 attending', null,'#globalModal');
        $I->click('Close', '#globalModal');

        $I->wantToTest('Adding a new space member and using then using the add all members again');

        $I->waitForElementVisible('.controls-header');
        $I->wait(1);
        $I->click('Invite', '.controls-header');

        $I->waitForText('Invite members', null, '#globalModal');
        $I->click('[for="inviteform-allregisteredusers"]');
        $I->click('[for="inviteform-withoutinvite"]');
        $I->click('Send', '#globalModal');

        $I->waitForElementNotVisible('#globalModal', 30);

        $I->waitForElementVisible('.fc-event');
        $I->jsClick('.fc-event');

        $I->waitForText('New Test Event',null, '#globalModal');
        $I->click('Edit', '#globalModal .modal-footer');

        $I->waitForText('Edit event', null, '#globalModal');
        $I->click('Participation', '#globalModal');
        $I->waitForElementVisible('[for="calendarentryform-forcejoin"]', null, '#globalModal');
        $I->click('[for="calendarentryform-forcejoin"]');
        $I->click('Save', '#globalModal');

        $I->waitForText('4 attending', null,'#globalModal');

        $I->wantToTest('closing the event');

        $I->jsClick('[data-action-click="toggleClose"]');
        $I->wait(5);

        $I->click('Close', '#globalModal');
        $I->wait(5);

        $I->amUser1(true);

        $I->expectTo('see the add and cancel notification');
        $I->seeInNotifications('Admin Tester just added you to event');
        $I->seeInNotifications('Admin Tester canceled event');

        $I->wantTo('make sure normal users can\'t see the add all space members feature');
        $I->amOnSpace(1, '/calendar/view/index');

        // Workaround regarding Webdriver click issues...
        $I->wait(1);

        // Workaround regarding Webdriver click issues...
        $I->click('.calendar_filter_mine');

        $I->wait(5);
        $I->waitForElementVisible('.fc-today');
        $I->click('.fc-today');

        $I->waitForText('Create event', null, '#globalModal');
        $I->click('Participation', '#globalModal');
        $I->wait(1);
        $I->dontSeeElement('[for="calendarentryform-forcejoin"]');
    }
}