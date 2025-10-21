<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace calendar\acceptance;

use calendar\AcceptanceTester;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;

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
        $I->waitForText('Calendar', 10, '.layout-nav-container');

        $I->amGoingTo('create a new entry');
        $I->click('Calendar', '.layout-nav-container');
        $I->waitForElementVisible('.fc-today');
        $I->click('.fc-today');
        $I->waitForText('Create Event', 10, '#globalModal');

        $I->fillField('CalendarEntry[title]', 'My Test Entry');
        $I->fillField('#calendarentry-description .humhub-ui-richtext', 'My Test Entry Description');

        $I->wantToTest('the hide/show functionality for time values (all day selection)');
        $I->seeElement('#calendarentryform-start_time:not(:disabled)');
        $I->seeElement('#calendarentryform-end_time:not(:disabled)');

        $I->click('[for="calendarentry-all_day"]');

        $I->wait(1);
        $I->seeElement('#calendarentryform-start_time:disabled');
        $I->seeElement('#calendarentryform-end_time:disabled');

        $I->amGoingTo('Save my new calendar entry');
        $I->click('Next', '#globalModal');
        $I->expectTo('see my event loaded into my modal');
        $I->waitForText('My Test Entry', 10, '#globalModal');
        $I->waitForText('Next', 10, '#globalModal');
        $I->click('Next', '#globalModal');
        $I->waitForText('Save', 10, '#globalModal');
        $I->click('Save', '#globalModal');

        $I->wait(1);

        $I->wantToTest('if my new entry was loaded into my calendar');
        $I->waitForElementVisible('.fc-event-container');
        $I->expectTo('see my entry title in my calendar');
        $I->see('My Test Entry', '.fc-event-container');

        $I->amOnSpace1();
        $I->waitForText('My Test Entry', 10, '[data-stream-entry]');
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
        $I->waitForText('Create Event');

        $I->fillField('CalendarEntry[title]', 'New Test Event');
        $I->click('[for="calendarentry-all_day"]');

        $I->click('Next', '#globalModal');
        $I->waitForText('Participants', 10, '#globalModal');
        $I->click('Next', '#globalModal');
        $I->waitForElementVisible('[for="calendarentryparticipationform-forcejoin"]', 10, '#globalModal');
        $I->click('[for="calendarentryparticipationform-forcejoin"]');
        $I->click('Save', '#globalModal');
        $I->seeSuccess();

        $I->expect('All space members to be attending');
        $I->click('New Test Event');
        $I->waitForText('2 Invited', 10, '#globalModal');
        $I->amOnSpace(1, '/calendar/view/index');

        $I->wantToTest('Adding a new space member and using then using the add all members again');

        $I->waitForElementVisible('.controls-header');
        $I->wait(1);
        $I->click('Invite', '.controls-header');

        $I->waitForText('Invite members', 10, '#globalModal');
        $I->click('[for="inviteform-allregisteredusers"]');
        $I->click('[for="inviteform-withoutinvite"]');
        $I->click('Send', '#globalModal');

        $I->amOnSpace(1, '/calendar/view/index');

        $I->waitForElementVisible('.fc-event');
        $I->jsClick('.fc-event');

        $I->waitForText('New Test Event', 10, '#globalModal');
        $I->click('Invite', '#globalModal .modal-footer');

        $I->waitForText('Participants', 10, '#globalModal');
        $I->waitForElementVisible('[for="calendarentryparticipationform-forcejoin"]', 10, '#globalModal');
        $I->click('[for="calendarentryparticipationform-forcejoin"]');
        $I->click('Save', '#globalModal');
        $I->seeSuccess();
        $I->click('New Test Event');

        $memberCount = Membership::getSpaceMembersQuery(Space::findOne(['id' => 1]))->count();

        $I->waitForText($memberCount . ' Invited', 10, '#globalModal');

        $I->amUser1(true);
        $I->waitForText('You are invited, please select your role:');
        $I->click('Attend');

        $I->amAdmin(true);
        $I->waitForText('You are invited, please select your role:');

        $I->wantToTest('closing the event');
        $I->jsClick('[data-action-click="toggleClose"]');
        $I->wait(5);

        $I->amUser1(true);

        $I->expectTo('see the add and cancel notification');
        $I->seeInNotifications('Admin Tester added you to the event');
        $I->seeInNotifications('Admin Tester canceled the event');

        $I->wantTo('make sure normal users can\'t see the add all space members feature');
        $I->amOnSpace(1, '/calendar/view/index');

        // Workaround regarding Webdriver click issues...
        $I->wait(1);

        // Workaround regarding Webdriver click issues...
        $I->click('.filter-toggle-link');
        $I->waitForText('My events');
        $I->click('.calendar_filter_mine');

        $I->wait(5);
        $I->waitForElementVisible('.fc-today');
        $I->click('.fc-today');

        $I->waitForText('Create Event', 10, '#globalModal');
        $I->fillField('CalendarEntry[title]', 'User Test Event 2');
        $I->click('Next', '#globalModal');
        $I->waitForText('Participants', 10, '#globalModal');
        $I->click('Next', '#globalModal');
        $I->wait(1);
        $I->dontSeeElement('[for="calendarentryparticipationform-forcejoin"]');
    }
}
