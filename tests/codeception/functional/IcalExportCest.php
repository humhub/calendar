<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace calendar\functional;

use calendar\FunctionalTester;
use humhub\modules\calendar\helpers\ical\IcalTokenService;
use humhub\modules\space\behaviors\SpaceModelModules;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Sabre\VObject\Reader;
use tests\codeception\_pages\DashboardPage;
use Yii;

class IcalExportCest
{
    public function testIcalExportForAdmin(FunctionalTester $I)
    {
        $I->amAdmin();
        $user = User::findOne(['id' => 1]);

        $user->moduleManager->enable('calendar');
        $user->moduleManager->flushCache();
        Yii::$app->moduleManager->flushCache();

        $jwtKey = IcalTokenService::instance()->encrypt($user->id, $user->guid, false);

        $I->amOnRoute('/calendar/export/calendar', ['token' => $jwtKey]);

        $I->seeResponseCodeIs(200);

        $headers = \Yii::$app->response->headers->toArray();

        $I->assertArrayHasKey('content-type', $headers, 'Content-Type header is present');
        $I->assertContains('text/calendar', $headers['content-type'], 'Content-Type is text/calendar');
        $I->assertArrayHasKey('content-disposition', $headers, 'Content-Disposition header is present');
        $I->assertStringContainsString('attachment; filename="admin_tester.ics"', $headers['content-disposition'][0], 'Content-Disposition includes filename');

        $icsContent = $I->grabResponse();
        $vcalendar = Reader::read($icsContent);

        $I->assertNotNull($vcalendar, 'iCal content is valid');
        $I->assertStringContainsString('BEGIN:VCALENDAR', $icsContent);
        $I->assertStringContainsString('VERSION:2.0', $icsContent);

        $events = iterator_to_array($vcalendar->VEVENT);
        $I->assertEquals(3, count($events), 'Admin should see 3 events');

        $teamMeeting = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Team Meeting');
        $teamMeeting = reset($teamMeeting);

        $I->assertNotFalse($teamMeeting, 'Team Meeting event exists');
        $I->assertEquals('Team Meeting', (string)$teamMeeting->SUMMARY);
        $I->assertEquals('20250601T140000', (string)$teamMeeting->DTSTART);
        $I->assertEquals('20250601T150000', (string)$teamMeeting->DTEND);
        $I->assertEquals('Conference Room A', (string)$teamMeeting->LOCATION);
        $I->assertEquals('Weekly team sync-up', (string)$teamMeeting->DESCRIPTION);
        $I->assertEquals(4, count($teamMeeting->ATTENDEE), 'Team Meeting has 4 attendees');
        $I->assertStringContainsString('ATTENDEE:Admin Tester:MAILTO:admin@example.com', $icsContent, 'Admin is Attendee');
        $I->assertStringContainsString('ATTENDEE:Peter Tester:MAILTO:user1@example.com', $icsContent, 'Peter is Attendee');
        $I->assertStringContainsString('ATTENDEE:Sara Tester:MAILTO:user2@example.com', $icsContent, 'Sara is Attendee');
        $I->assertStringContainsString('ATTENDEE:Andreas Tester:MAILTO:user3@example.com', $icsContent, 'Andreas is Attendee');

    }

    public function testGlobalIcalExportSpecialCharsForUser1(FunctionalTester $I)
    {
        $I->amUser1();
        $user = User::findOne(['id' => 2]);

        $user->moduleManager->enable('calendar');
        $user->moduleManager->flushCache();
        Yii::$app->moduleManager->flushCache();

        $jwtKey = IcalTokenService::instance()->encrypt($user->id, $user->guid, true);

        $I->amOnRoute('/calendar/export/calendar', ['token' => $jwtKey]);

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'text/calendar');

        $icsContent = $I->grabResponse();
        $vcalendar = Reader::read($icsContent);

        $I->assertNotNull($vcalendar, 'iCal content is valid');
        $I->assertStringContainsString('BEGIN:VCALENDAR', $icsContent);
        $I->assertStringContainsString('VERSION:2.0', $icsContent);

        $events = iterator_to_array($vcalendar->VEVENT);
        $I->assertEquals(3, count($events), 'User1 should see 3 events');

        $specialEvent = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Event with Comma, & Semicolon;');
        $specialEvent = reset($specialEvent);
        $I->assertNotFalse($specialEvent, 'Special characters event exists');
        $I->assertEquals('Event with Comma, & Semicolon;', (string)$specialEvent->SUMMARY);
        $I->assertEquals('20250602T180000', (string)$specialEvent->DTSTART);
        $I->assertEquals('20250602T190000', (string)$specialEvent->DTEND);
        $I->assertEquals('Room B, Building 1', (string)$specialEvent->LOCATION);
        $I->assertEquals('Test special chars: \n new line', (string)$specialEvent->DESCRIPTION);
        $I->assertEquals(3, count($specialEvent->ATTENDEE), 'Special characters event has 3 attendees');
        $I->assertStringContainsString('ATTENDEE:Admin Tester:MAILTO:admin@example.com', $icsContent, 'Admin is Attendee');
        $I->assertStringContainsString('ATTENDEE:Peter Tester:MAILTO:user1@example.com', $icsContent, 'Peter is Attendee');
        $I->assertStringContainsString('ATTENDEE:Sara Tester:MAILTO:user2@example.com', $icsContent, 'Sara is Attendee');
        $I->assertStringContainsString('ATTENDEE:Andreas Tester:MAILTO:user3@example.com', $icsContent, 'Andreas is Attendee');

    }
}
