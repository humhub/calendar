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
        $I->waitForText('Create event');

        $I->fillField('CalendarEntry[title]', 'My Test Entry');
        $I->fillField('CalendarEntry[description]', 'My Test Entry Description');

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
}