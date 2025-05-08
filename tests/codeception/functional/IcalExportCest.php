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

        $jwtKey = IcalTokenService::instance()->encrypt($user->id, $user->guid);

        $I->amOnRoute('remote/ical', ['token' => $jwtKey]);

        $I->seeResponseCodeIs(200);

        $headers = \Yii::$app->response->headers->toArray();
        $I->assertArrayHasKey('content-type', $headers, 'Content-Type header is present');
        $I->assertContains('text/calendar', $headers['content-type'], 'Content-Type is text/calendar');
        $I->assertArrayHasKey('content-disposition', $headers, 'Content-Disposition header is present');
        $I->assertStringContainsString('attachment; filename="calendar.ics"', $headers['content-disposition'][0], 'Content-Disposition includes filename');

        $icsContent = $I->grabResponse();
        $vcalendar = Reader::read($icsContent);

        $I->assertNotNull($vcalendar, 'iCal content is valid');
        $I->assertStringContainsString('BEGIN:VCALENDAR', $icsContent);
        $I->assertStringContainsString('VERSION:2.0', $icsContent);

        $events = $vcalendar->VEVENT;
        $I->assertCount(2, $events, 'Admin should see 2 events');

        $teamMeeting = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Team Meeting' && (string)$event->DTSTART === '20250601T100000');
        $teamMeeting = reset($teamMeeting);
        $I->assertNotFalse($teamMeeting, 'Team Meeting event exists');
        $I->assertEquals('Team Meeting', (string)$teamMeeting->SUMMARY);
        $I->assertEquals('20250601T100000', (string)$teamMeeting->DTSTART);
        $I->assertEquals('20250601T110000', (string)$teamMeeting->DTEND);
        $I->assertEquals('Conference Room A', (string)$teamMeeting->LOCATION);
        $I->assertEquals('Weekly team sync-up', (string)$teamMeeting->DESCRIPTION);
        $I->assertCount(4, $teamMeeting->ATTENDEE, 'Team Meeting has 4 attendees');
        $I->assertStringContainsString('ATTENDEE;PARTSTAT=ACCEPTED;CN=Admin', (string)$teamMeeting, 'Admin is accepted');
        $I->assertStringContainsString('ATTENDEE;PARTSTAT=NEEDS-ACTION;CN=User1', (string)$teamMeeting, 'User1 is maybe');
        $I->assertStringContainsString('ATTENDEE;PARTSTAT=ACCEPTED;CN=User2', (string)$teamMeeting, 'User2 is accepted');
        $I->assertStringContainsString('ATTENDEE;PARTSTAT=DECLINED;CN=User3', (string)$teamMeeting, 'User3 is declined');

        $recurringMeeting = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Team Meeting' && (string)$event->DTSTART === '20250608T100000');
        $recurringMeeting = reset($recurringMeeting);
        $I->assertNotFalse($recurringMeeting, 'Recurring Team Meeting event exists');
        $I->assertEquals('Team Meeting', (string)$recurringMeeting->SUMMARY);
        $I->assertEquals('20250608T100000', (string)$recurringMeeting->DTSTART);
        $I->assertEquals('20250608T110000', (string)$recurringMeeting->DTEND);
        $I->assertEquals('FREQ=WEEKLY;INTERVAL=1;BYDAY=SU', (string)$recurringMeeting->RRULE);
        $I->assertEquals('20250608T100000', (string)$recurringMeeting->{'RECURRENCE-ID'});
        $I->assertCount(3, $recurringMeeting->ATTENDEE, 'Recurring Team Meeting has 3 attendees');
        $I->assertStringContainsString('ATTENDEE;PARTSTAT=NEEDS-ACTION;CN=Admin', (string)$recurringMeeting, 'Admin is invited');
        $I->assertStringContainsString('ATTENDEE;PARTSTAT=NEEDS-ACTION;CN=User1', (string)$recurringMeeting, 'User1 is maybe');
        $I->assertStringContainsString('ATTENDEE;PARTSTAT=ACCEPTED;CN=User2', (string)$recurringMeeting, 'User2 is accepted');
    }

    public function testIcalExportForUser1(FunctionalTester $I)
    {
        $I->amUser2();

//        $I->amOnRoute('/calendar/export');

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'text/calendar');

        $icsContent = $I->grabResponse();
        $vcalendar = Reader::read($icsContent);

        $events = $vcalendar->VEVENT;
        $I->assertCount(3, $events, 'User1 should see 3 events');

        $specialEvent = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Event with Comma, & Semicolon;');
        $specialEvent = reset($specialEvent);
        $I->assertNotFalse($specialEvent, 'Special characters event exists');
        $I->assertEquals('Event with Comma, & Semicolon;', (string)$specialEvent->SUMMARY);
        $I->assertEquals('20250602T140000', (string)$specialEvent->DTSTART);
        $I->assertEquals('20250602T150000', (string)$specialEvent->DTEND);
        $I->assertEquals('Room B, Building 1', (string)$specialEvent->LOCATION);
        $I->assertEquals('Test special chars: \n new line', (string)$specialEvent->DESCRIPTION);
        $I->assertCount(3, $specialEvent->ATTENDEE, 'Special characters event has 3 attendees');
        $I->assertStringContainsString('ATTENDEE;PARTSTAT=ACCEPTED;CN=User1', (string)$specialEvent, 'User1 is accepted');
        $I->assertStringContainsString('ATTENDEE;PARTSTAT=DECLINED;CN=User2', (string)$specialEvent, 'User2 is declined');
        $I->assertStringContainsString('ATTENDEE;PARTSTAT=NEEDS-ACTION;CN=User3', (string)$specialEvent, 'User3 is invited');
    }

    public function testIcalExportForUser2(FunctionalTester $I)
    {
        $I->amUser3();

//        $I->amOnRoute('/calendar/export');

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'text/calendar');

        $icsContent = $I->grabResponse();
        $vcalendar = Reader::read($icsContent);

        $events = $vcalendar->VEVENT;
        $I->assertCount(3, $events, 'User2 should see 3 evenimente');

        $teamMeeting = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Team Meeting' && (string)$event->DTSTART === '20250601T100000');
        $teamMeeting = reset($teamMeeting);
        $I->assertNotFalse($teamMeeting, 'Team Meeting event exists');
        $I->assertCount(4, $teamMeeting->ATTENDEE, 'Team Meeting has 4 attendees');
        $I->assertStringContainsString('ATTENDEE;PARTSTAT=ACCEPTED;CN=User2', (string)$teamMeeting, 'User2 is accepted');
    }
}
