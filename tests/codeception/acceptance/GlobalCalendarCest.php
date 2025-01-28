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
class GlobalCalendarCest
{
    /**
     * @param AcceptanceTester $I
     * @return void
     */
    public function testGlobalCalendarCreateEntry(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->enableModule(1, 'calendar');

        $I->amGoingTo('create a new space event as moderator');
        $I->amUser2(true);
        $I->amOnSpace1('/calendar/view');
        $I->createEventToday('Space Event', 'Space Event Description');

        $I->wantToTest('the if the space event is visible in my global calendar');
        $I->amOnRoute(['/calendar/global/index']);
        $I->expectTo('see my space calendar entry');
        $I->see('Select calendars');
        $I->waitForText('Space Event', null, '#calendar');

        $I->amGoingTo('activate the profile calendar module by creating a new event');
        $I->click('.fc-today');
        $I->expectTo('see the choose calendar modal');
        $I->waitForText('Choose target calendar');
        $I->click('.select2-selection--single');
        $I->click('.select2-results__option:nth-child(2)');
        $I->click('Next', '#globalModal');


        $I->waitForText('Add profile calendar', null, '#globalModal');
        $I->click('Enable', '#globalModal');
        $I->waitForText('Next', null, '#globalModal');
        $I->click('Next', '#globalModal');

        $I->waitForText('Create Event', null, '#globalModal');
        $I->fillField('CalendarEntry[title]', 'My Test Profile Entry');
        $I->fillField('#calendarentry-description .humhub-ui-richtext', 'My Test Profile Entry Description');
        $I->click('Next', '#globalModal');

        //$I->waitForText('Close', null, '#globalModal');
        //$I->click('Close', '#globalModal');

        //$I->wait(1);
        $I->waitForText('My Test Profile Entry', null, '.fc-event-container');
        $I->see('My Test Profile Entry', '.fc-title');

        // Active space filter
        $I->amGoingTo('Select the space calendar filter');
        $I->click('.calendar_my_spaces');
        $I->wait(2);
        $I->waitForText('Space Event', null, '#calendar');

        $I->wantToTest('the global calendar filters');
        $I->amGoingTo('deselect the space calendar filter');
        $I->click('.calendar_my_spaces');
        $I->wait(2);
        $I->waitForText('Space Event', null, '#calendar');
    }

    /**
     * @param AcceptanceTester $I
     * @return void
     * @skip This test fails, but manually works fine, need to figure out why!
     */
    public function testChangeEventTime(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->enableModule(1, 'calendar');

        $I->amGoingTo('create a new space event as moderator');
        $I->amUser2(true);
        $I->amOnSpace1('/calendar/view');

        $I->waitForElementVisible('.fc-today');
        $I->click('.fc-day-top.fc-today');
        $I->waitForText('Create Event');

        //         $I->click('[for="calendarentry-all_day"]');
        //         $I->wait(1);

        $I->wantToTest('how the end time will change value if the start time is changed');
        $I->fillField('#calendarentryform-start_time', '01:00 PM');
        //         $I->fillField('input[name="CalendarEntryForm[start_time]"]', '01:00 PM');
        //         $I->executeJS('$(".field-calendarentryform-start_time .picker").click();');
        //         $I->seeElement('input', ['name' => 'meridian']);
        //         $I->fillField('input[name="hour"]', '01');
        //         $I->fillField('input[name="minute"]', '00');
        //         $I->fillField('input[name="meridian"]', 'PM');
        $I->executeJS('$("#calendarentryform-start_time").focus().val("01:00 PM").change();');
        $I->wait(1);
        $I->seeInField('#calendarentryform-end_time', '02:00 PM');

        $I->wantToTest('how the start time will change value if the end time is changed');
        $I->fillField('#calendarentryform-end_time', '02:00 AM');
        $I->executeJS('$("#calendarentryform-end_time").trigger("change");');
        $I->wait(1);
        $I->seeInField('#calendarentryform-start_time', '01:00 AM');
    }
}
