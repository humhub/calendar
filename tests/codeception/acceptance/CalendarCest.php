<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace calendar\acceptance;

use calendar\AcceptanceTester;

class CalendarCest
{
    
    public function testInstallAndCreatEntry(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->wantToTest('the creation of a calendar entry');
        $I->amGoingTo('install the calendar module for space 1');
        $I->amOnPage('index-test.php?r=space/manage/module&sguid=5396d499-20d6-4233-800b-c6c86e5fa34a');

        $I->click('Enable');
        $I->waitForText('Disable');
        $I->amOnSpace1();
        $I->waitForText('Calendar', null, '.layout-nav-container');
        $I->click('Calendar', '.layout-nav-container');
        $I->waitForElementVisible('.fc-today');
        $I->click('.fc-today');
        $I->waitForText('Create event');

        $I->fillField('CalendarEntry[title]', 'My Test Entry');
        $I->fillField('CalendarEntry[description]', 'My Test Entry Description');

        $I->wantToTest('the hide/show functionality for time values (all day selection)');
        $I->dontSeeElement('.field-calendarentry-start_time');
        $I->dontSeeElement('.field-calendarentry-end_time');
        $I->click('.field-allDayCheckbox label');
        $I->wait(1);
        $I->seeElement('.field-calendarentry-start_time');
        $I->seeElement('.field-calendarentry-end_time');
        $I->click('.field-allDayCheckbox label');
        $I->wait(1);
        $I->dontSeeElement('.field-calendarentry-start_time');
        $I->dontSeeElement('.field-calendarentry-end_time');

        $I->click('Save', '#globalModal');
        $I->waitForText('My Test Entry',null, '.fc-event-container');

        $I->click('.fc-event-container');
        $I->waitForText('My Test Entry', null, '#globalModal');
        $I->see('My Test Entry Description', '#globalModal');
        $I->see('Maybe', '#globalModal');
        $I->click('Maybe');
        $I->wait(2);
        $I->waitForText('My Test Entry', null, '#globalModal');
        $I->see('Maybe', 'button[disabled]');

        $I->click('Like', '#globalModal');
        $I->waitForText('Unlike', null, '#globalModal');

        $I->click('.btn-primary', '#globalModal .modal-footer');
        $I->waitForText('Edit event');
        $I->fillField('CalendarEntry[title]', 'My Test Entry Updated');

        $I->click('Save', '#globalModal');
        $I->waitForText('My Test Entry Updated',null, '.fc-event-container');

        /**
         * Global calendar test
         */
        $I->wantToTest('the global calendar');
        $I->amOnPage('index-test.php?r=calendar/global/index');
        $I->see('Select calendars');
        $I->waitForText('My Test Entry',null, '.fc-event-container');

        $I->click('.calendar_my_spaces');
        $I->wait(2);
        $I->cantSee('My Test Entry', '.fc-event-container');

        $I->click('.fc-today');
        $I->waitForText('Add profile calendar', null, '#globalModal');
        $I->click('Enable', '#globalModal');
        $I->waitForText('Next', null, '#globalModal');
        $I->click('Next', '#globalModal');

        $I->waitForText('Create event', null, '#globalModal');
        $I->fillField('CalendarEntry[title]', 'My Test Profile Entry');
        $I->fillField('CalendarEntry[description]', 'My Test Profile Entry Description');

        $I->click('Save', '#globalModal');

        $I->waitForText('My Test Profile Entry',null, '.fc-event-container');

        $I->click('.calendar_my_spaces');

        $I->waitForText('My Test Entry Updated',null, '.fc-event-container');
        $I->see('My Test Profile Entry',null, '.fc-event-container');
    }
}