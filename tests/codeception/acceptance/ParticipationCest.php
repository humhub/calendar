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
 * Time: 16:15
 */
class ParticipationCest
{
    public function testInstallAndCreatEntry(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->enableModule(1, 'calendar');

        $I->amGoingTo('create a new space event with');
        $I->amOnSpace1('/calendar/view');
        $I->createEventToday('Participant Event', 'Participant Description', null, null, false);
        $I->click('Next', '#globalModal');
        $I->waitForText('Maximum number of participants');

        $I->selectOption('#calendarentry-participation_mode', 2);

        $I->canSee('Maximum number of participants', '#globalModal');
        $I->canSee('Allow participation state', '#globalModal');

        $I->selectOption('#calendarentry-participation_mode', 0);

        $I->wait(1);

        $I->cantSee('Maximum number of participants', '#globalModal');
        $I->cantSee('Allow participation state', '#globalModal');

        $I->click('[type="submit"]');
        $I->wait(1);
        $I->click('Participant Event');
        $I->waitForText('Participant Description',null,'#globalModal');
        $I->dontSee('Attend', '#globalModal button');
        $I->dontSee('Maybe', '#globalModal button');
        $I->dontSee('Decline', '#globalModal button');

        $I->click('Invite', '#globalModal');
        $I->waitForText('Event Participants',null, '#globalModal');

        $I->selectOption('#calendarentry-participation_mode', 2);

        $I->fillField('#calendarentry-max_participants', 1);
        $I->fillField('#calendarentry-participant_info .humhub-ui-richtext', 'My Test Event');

        $I->click('[type="submit"]');
        $I->seeSuccess();
        $I->click('Participant Event');
        $I->waitForText('Participant Description',null, '#globalModal');
        $I->see('Attend', '#globalModal button');
        $I->see('Maybe', '#globalModal button');
        $I->see('Decline', '#globalModal button');

        $I->click('Invite', '#globalModal');
        $I->waitForText('Event Participants',null, '#globalModal');
        $I->click('.tab-participation');
        $I->click('[for="calendarentry-allow_decline"]', '#globalModal');
        $I->click('[for="calendarentry-allow_maybe"]', '#globalModal');
        $I->click('[type="submit"]');
        $I->wait(1);

        $I->click('Participant Event');
        $I->waitForText('Participant Description',null, '#globalModal');
        $I->see('Attend', '#globalModal button');
        $I->dontSee('Maybe', '#globalModal button');
        $I->dontSee('Decline', '#globalModal button');

        $I->click('Attend', '#globalModal');
        $I->waitForText('1 attending', null, '#globalModal');
        $I->click('1 attending', '#globalModal');
        $I->waitForText('Event Participants', null, '#globalModal');
        $I->see('Admin Tester', '#globalModal');
    }
}