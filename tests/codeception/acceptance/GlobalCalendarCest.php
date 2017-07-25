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
        $I->waitForText('Space Event',null, '.fc-event-container');

        $I->wantToTest('the global calendar filters');
        $I->amGoingTo('deselect the space clalendar filter');
        $I->click('.calendar_my_spaces');
        $I->wait(2);
        $I->cantSee('Space Event', '.fc-event-container');

        $I->amGoingTo('activate the profile calendar module by creating a new event');
        $I->click('.fc-today');
        $I->expectTo('see the activate profile calendar modal');
        $I->waitForText('Add profile calendar', null, '#globalModal');
        $I->click('Enable', '#globalModal');
        $I->waitForText('Next', null, '#globalModal');
        $I->click('Next', '#globalModal');

        $I->waitForText('Create event', null, '#globalModal');
        $I->fillField('CalendarEntry[title]', 'My Test Profile Entry');
        $I->fillField('CalendarEntry[description]', 'My Test Profile Entry Description');
        $I->click('Save', '#globalModal');

        $I->waitForText('Close', null, '#globalModal');
        $I->click('Close', '#globalModal');

        $I->wait(1);
        $I->waitForText('My Test Profile Entry',null, '.fc-event-container');

        $I->waitForElementVisible('.calendar_my_spaces');
        $I->click('.calendar_my_spaces');

        $I->waitForText('Space Event');
        $I->see('My Test Profile Entry', '.fc-title');
    }
}