<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace calendar\functional;

use calendar\FunctionalTester;
use humhub\modules\calendar\helpers\AuthTokenService;
use humhub\modules\space\behaviors\SpaceModelModules;
use humhub\modules\user\models\User;
use Sabre\VObject\Reader;
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
        Yii::$app->getModule('calendar')->settings->set('includeUserInfo', true);

        $entry = $I->createCalendarEntry(
            $user,
            [
                'title' => 'Team Meeting',
                'description' => 'Weekly team sync-up',
                'start_datetime' => '2025-06-01 10:00:00',
                'end_datetime' => '2025-06-01 11:00:00',
                'all_day' => 0,
                'participation_mode' => 2, // Allow participation
                'color' => '#007bff',
                'allow_decline' => 1,
                'allow_maybe' => 1,
                'time_zone' => 'Asia/Yerevan',
                'participant_info' => '',
                'closed' => 0,
                'max_participants' => null,
                'uid' => 'event-001-20250601',
                'rrule' => null,
                'parent_event_id' => null,
                'recurrence_id' => null,
                'exdate' => null,
                'sequence' => 0,
                'location' => 'Conference Room A',
            ],
            [1, 2, 3, 4],
        );

        $jwtKey = AuthTokenService::instance()->iCalEncrypt($user->id, $user->guid, false);

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

        $teamMeeting = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Team Meeting');
        $teamMeeting = reset($teamMeeting);

        $I->assertNotFalse($teamMeeting, 'Team Meeting event exists');
        $I->assertEquals('Team Meeting', (string)$teamMeeting->SUMMARY);
        $I->assertEquals('20250601T100000', (string)$teamMeeting->DTSTART);
        $I->assertEquals('20250601T110000', (string)$teamMeeting->DTEND);
        $I->assertEquals('Conference Room A', (string)$teamMeeting->LOCATION);
        $I->assertEquals('Weekly team sync-up', (string)$teamMeeting->DESCRIPTION);
        $I->assertEquals(4, count($teamMeeting->ATTENDEE), 'Team Meeting has 4 attendees');
        $I->assertStringContainsString('ATTENDEE:Admin Tester:MAILTO:admin@example.com', $icsContent, 'Admin is Attendee');
        $I->assertStringContainsString('ATTENDEE:Peter Tester:MAILTO:user1@example.com', $icsContent, 'Peter is Attendee');
        $I->assertStringContainsString('ATTENDEE:Sara Tester:MAILTO:user2@example.com', $icsContent, 'Sara is Attendee');
        $I->assertStringContainsString('ATTENDEE:Andreas Tester:MAILTO:user3@example.com', $icsContent, 'Andreas is Attendee');

        $entry->hardDelete();
    }

    public function testGlobalIcalExportSpecialCharsForUser1(FunctionalTester $I)
    {
        $I->amUser1();
        $user = User::findOne(['id' => 2]);

        $user->moduleManager->enable('calendar');
        $user->moduleManager->flushCache();
        Yii::$app->moduleManager->flushCache();
        Yii::$app->getModule('calendar')->settings->set('includeUserInfo', true);

        $entry = $I->createCalendarEntry(
            $user,
            [
                'title' => 'Event with Comma, & Semicolon;',
                'description' => 'Test special chars: \n new line',
                'start_datetime' => '2025-06-02 14:00:00',
                'end_datetime' => '2025-06-02 15:00:00',
                'all_day' => 0,
                'participation_mode' => 2,
                'color' => '#28a745',
                'allow_decline' => 1,
                'allow_maybe' => 1,
                'time_zone' => 'Asia/Yerevan',
                'participant_info' => '',
                'closed' => 0,
                'max_participants' => null,
                'uid' => 'event-002-20250602',
                'rrule' => null,
                'parent_event_id' => null,
                'recurrence_id' => null,
                'exdate' => null,
                'sequence' => 0,
                'location' => 'Room B, Building 1',
            ],
            [2, 3, 4],
        );

        $jwtKey = AuthTokenService::instance()->iCalEncrypt($user->id, $user->guid, true);

        $I->amOnRoute('/calendar/export/calendar', ['token' => $jwtKey]);

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'text/calendar');

        $icsContent = $I->grabResponse();
        $vcalendar = Reader::read($icsContent);

        $I->assertNotNull($vcalendar, 'iCal content is valid');
        $I->assertStringContainsString('BEGIN:VCALENDAR', $icsContent);
        $I->assertStringContainsString('VERSION:2.0', $icsContent);

        $events = iterator_to_array($vcalendar->VEVENT);

        $specialEvent = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Event with Comma, & Semicolon;');
        $specialEvent = reset($specialEvent);
        $I->assertNotFalse($specialEvent, 'Special characters event exists');
        $I->assertEquals('Event with Comma, & Semicolon;', (string)$specialEvent->SUMMARY);
        $I->assertEquals('20250602T140000', (string)$specialEvent->DTSTART);
        $I->assertEquals('20250602T150000', (string)$specialEvent->DTEND);
        $I->assertEquals('Room B, Building 1', (string)$specialEvent->LOCATION);
        $I->assertEquals('Test special chars: \n new line', (string)$specialEvent->DESCRIPTION);
        $I->assertEquals(3, count($specialEvent->ATTENDEE), 'Special characters event has 3 attendees');
        $I->assertStringContainsString('ATTENDEE:Peter Tester:MAILTO:user1@example.com', $icsContent, 'Peter is Attendee');
        $I->assertStringContainsString('ATTENDEE:Sara Tester:MAILTO:user2@example.com', $icsContent, 'Sara is Attendee');
        $I->assertStringContainsString('ATTENDEE:Andreas Tester:MAILTO:user3@example.com', $icsContent, 'Andreas is Attendee');

        $entry->hardDelete();
    }
}
