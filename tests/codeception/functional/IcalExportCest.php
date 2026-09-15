<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace calendar\functional;

use calendar\FunctionalTester;
use humhub\modules\calendar\helpers\AuthTokenService;
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
        Yii::$app->getModule('calendar')->settings->set('includeParticipantInfo', true);
        Yii::$app->getModule('calendar')->settings->set('includeParticipantEmail', true);

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


        $I->assertNotNull($vcalendar, 'ics content is valid');
        $I->assertStringContainsString('BEGIN:VCALENDAR', $icsContent);
        $I->assertStringContainsString('VERSION:2.0', $icsContent);

        $events = iterator_to_array($vcalendar->VEVENT);

        $teamMeeting = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Team Meeting');
        $teamMeeting = reset($teamMeeting);

        $I->assertNotFalse($teamMeeting, 'Team Meeting event exists');
        $I->assertEquals('Team Meeting', (string)$teamMeeting->SUMMARY);
        $I->assertEquals('20250601T100000Z', (string)$teamMeeting->DTSTART);
        $I->assertEquals('20250601T110000Z', (string)$teamMeeting->DTEND);
        $I->assertEquals('Conference Room A', (string)$teamMeeting->LOCATION);
        $I->assertEquals('Weekly team sync-up', (string)$teamMeeting->DESCRIPTION);
        $I->assertEquals(4, count($teamMeeting->ATTENDEE), 'Team Meeting has 4 attendees');
        $I->assertStringContainsString('ATTENDEE;CN=ADMIN TESTER;PARTSTAT=ACCEPTED:mailto:admin@example.com', $icsContent, 'Admin is Attendee');
        $I->assertStringContainsString('ATTENDEE;CN=PETER TESTER;PARTSTAT=ACCEPTED:mailto:user1@example.com', $icsContent, 'Peter is Attendee');
        $I->assertStringContainsString('ATTENDEE;CN=SARA TESTER;PARTSTAT=ACCEPTED:mailto:user2@example.com', $icsContent, 'Sara is Attendee');
        $I->assertStringContainsString('ATTENDEE;CN=ANDREAS TESTER;PARTSTAT=ACCEPTED:mailto:user3@example.com', $icsContent, 'Andreas is Attendee');

        $entry->hardDelete();
    }

    public function testGlobalIcalExportSpecialCharsForUser1(FunctionalTester $I)
    {
        $I->amUser1();
        $user = User::findOne(['id' => 2]);

        $user->moduleManager->enable('calendar');
        $user->moduleManager->flushCache();
        Yii::$app->moduleManager->flushCache();
        Yii::$app->getModule('calendar')->settings->set('includeParticipantInfo', true);
        Yii::$app->getModule('calendar')->settings->set('includeParticipantEmail', true);

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

        $I->assertNotNull($vcalendar, 'ics content is valid');
        $I->assertStringContainsString('BEGIN:VCALENDAR', $icsContent);
        $I->assertStringContainsString('VERSION:2.0', $icsContent);

        $events = iterator_to_array($vcalendar->VEVENT);

        $specialEvent = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Event with Comma, & Semicolon;');
        $specialEvent = reset($specialEvent);
        $I->assertNotFalse($specialEvent, 'Special characters event exists');
        $I->assertEquals('Event with Comma, & Semicolon;', (string)$specialEvent->SUMMARY);
        $I->assertEquals('20250602T140000Z', (string)$specialEvent->DTSTART);
        $I->assertEquals('20250602T150000Z', (string)$specialEvent->DTEND);
        $I->assertEquals('Room B, Building 1', (string)$specialEvent->LOCATION);
        $I->assertEquals('Test special chars: \n new line', (string)$specialEvent->DESCRIPTION);
        $I->assertEquals(3, count($specialEvent->ATTENDEE), 'Special characters event has 3 attendees');
        $I->assertStringContainsString('ATTENDEE;CN=PETER TESTER;PARTSTAT=ACCEPTED:mailto:user1@example.com', $icsContent, 'Peter is Attendee');
        $I->assertStringContainsString('ATTENDEE;CN=SARA TESTER;PARTSTAT=ACCEPTED:mailto:user2@example.com', $icsContent, 'Sara is Attendee');
        $I->assertStringContainsString('ATTENDEE;CN=ANDREAS TESTER;PARTSTAT=ACCEPTED:mailto:user3@example.com', $icsContent, 'Andreas is Attendee');

        $entry->hardDelete();
    }

    public function testIcsWithDisabledIncludeParticipantInfo(FunctionalTester $I)
    {
        $I->amAdmin();
        $user = User::findOne(['id' => 1]);

        $user->moduleManager->enable('calendar');
        $user->moduleManager->flushCache();
        Yii::$app->moduleManager->flushCache();
        Yii::$app->getModule('calendar')->settings->set('includeParticipantInfo', false);
        Yii::$app->getModule('calendar')->settings->set('includeParticipantEmail', false);

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


        $I->assertNotNull($vcalendar, 'ics content is valid');
        $I->assertStringContainsString('BEGIN:VCALENDAR', $icsContent);
        $I->assertStringContainsString('VERSION:2.0', $icsContent);

        $events = iterator_to_array($vcalendar->VEVENT);

        $teamMeeting = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Team Meeting');
        $teamMeeting = reset($teamMeeting);

        $I->assertNotFalse($teamMeeting, 'Team Meeting event exists');
        $I->assertEquals('Team Meeting', (string)$teamMeeting->SUMMARY);
        $I->assertEquals('20250601T100000Z', (string)$teamMeeting->DTSTART);
        $I->assertEquals('20250601T110000Z', (string)$teamMeeting->DTEND);
        $I->assertEquals('Conference Room A', (string)$teamMeeting->LOCATION);
        $I->assertEquals('Weekly team sync-up', (string)$teamMeeting->DESCRIPTION);
        $I->assertNull($teamMeeting->ATTENDEE, 'Team Meeting has not any attendees');
        $I->assertStringNotContainsString('ATTENDEE;CN=ADMIN TESTER;PARTSTAT=ACCEPTED:mailto:admin@example.com', $icsContent, 'Admin is Attendee');
        $I->assertStringNotContainsString('ATTENDEE;CN=PETER TESTER;PARTSTAT=ACCEPTED:mailto:user1@example.com', $icsContent, 'Peter is Attendee');
        $I->assertStringNotContainsString('ATTENDEE;CN=SARA TESTER;PARTSTAT=ACCEPTED:mailto:user2@example.com', $icsContent, 'Sara is Attendee');
        $I->assertStringNotContainsString('ATTENDEE;CN=ANDREAS TESTER;PARTSTAT=ACCEPTED:mailto:user3@example.com', $icsContent, 'Andreas is Attendee');

        $entry->hardDelete();
    }

    public function testIcsWithDisabledIncludeParticipantEmail(FunctionalTester $I)
    {
        $I->amAdmin();
        $user = User::findOne(['id' => 1]);

        $user->moduleManager->enable('calendar');
        $user->moduleManager->flushCache();
        Yii::$app->moduleManager->flushCache();
        Yii::$app->getModule('calendar')->settings->set('includeParticipantInfo', true);
        Yii::$app->getModule('calendar')->settings->set('includeParticipantEmail', false);

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


        $I->assertNotNull($vcalendar, 'ics content is valid');
        $I->assertStringContainsString('BEGIN:VCALENDAR', $icsContent);
        $I->assertStringContainsString('VERSION:2.0', $icsContent);

        $events = iterator_to_array($vcalendar->VEVENT);

        $teamMeeting = array_filter($events, fn($event) => (string)$event->SUMMARY === 'Team Meeting');
        $teamMeeting = reset($teamMeeting);

        $I->assertNotFalse($teamMeeting, 'Team Meeting event exists');
        $I->assertEquals('Team Meeting', (string)$teamMeeting->SUMMARY);
        $I->assertEquals('20250601T100000Z', (string)$teamMeeting->DTSTART);
        $I->assertEquals('20250601T110000Z', (string)$teamMeeting->DTEND);
        $I->assertEquals('Conference Room A', (string)$teamMeeting->LOCATION);
        $I->assertEquals('Weekly team sync-up', (string)$teamMeeting->DESCRIPTION);
        $I->assertEquals(4, count($teamMeeting->ATTENDEE), 'Team Meeting has 4 attendees');
        $I->assertStringContainsString('ATTENDEE;CN=ADMIN TESTER;PARTSTAT=ACCEPTED:mailto:-', $icsContent, 'Admin is Attendee');
        $I->assertStringContainsString('ATTENDEE;CN=PETER TESTER;PARTSTAT=ACCEPTED:mailto:-', $icsContent, 'Peter is Attendee');
        $I->assertStringContainsString('ATTENDEE;CN=SARA TESTER;PARTSTAT=ACCEPTED:mailto:-', $icsContent, 'Sara is Attendee');
        $I->assertStringContainsString('ATTENDEE;CN=ANDREAS TESTER;PARTSTAT=ACCEPTED:mailto:-', $icsContent, 'Andreas is Attendee');

        $entry->hardDelete();
    }

    /**
     * Regression test for https://github.com/humhub/humhub/issues/8478.
     *
     * Events created in a timezone different from each other (and from the site's
     * default timezone) must each get their own VTIMEZONE component. Otherwise the
     * DTSTART/DTEND TZID parameter references a timezone that is never declared
     * anywhere in the file (RFC 5545 3.6.5), which strict iCalendar consumers -
     * notably Google Calendar's importer - reject outright ("Imported 0 out of 0
     * events") instead of just skipping the offending event.
     */
    public function testIcalExportDeclaresVtimezoneForEveryEventTimezone(FunctionalTester $I)
    {
        $I->amAdmin();
        $user = User::findOne(['id' => 1]);

        $user->moduleManager->enable('calendar');
        $user->moduleManager->flushCache();
        Yii::$app->moduleManager->flushCache();

        $entryBerlin = $I->createCalendarEntry(
            $user,
            [
                'title' => 'Berlin Event',
                'description' => 'Regression test event',
                'start_datetime' => '2026-09-17 10:00:00',
                'end_datetime' => '2026-09-17 11:00:00',
                'all_day' => 0,
                'participation_mode' => 0,
                'color' => '#007bff',
                'allow_decline' => 0,
                'allow_maybe' => 0,
                'time_zone' => 'Europe/Berlin',
                'participant_info' => '',
                'closed' => 0,
                'max_participants' => null,
                'uid' => 'event-tz-berlin-20260917',
                'rrule' => null,
                'parent_event_id' => null,
                'recurrence_id' => null,
                'exdate' => null,
                'sequence' => 0,
                'location' => '',
            ],
            [],
        );
        // 'time_zone' is not in CalendarEntry::rules(), so setAttributes() (safe-only)
        // silently drops it and init() defaults to the acting user's own timezone
        // instead. Force it directly so the event actually uses a non-UTC timezone.
        $entryBerlin->time_zone = 'Europe/Berlin';
        $entryBerlin->save();

        $entryManaus = $I->createCalendarEntry(
            $user,
            [
                'title' => 'Manaus Event',
                'description' => 'Regression test event',
                'start_datetime' => '2026-09-18 08:00:00',
                'end_datetime' => '2026-09-18 09:00:00',
                'all_day' => 0,
                'participation_mode' => 0,
                'color' => '#28a745',
                'allow_decline' => 0,
                'allow_maybe' => 0,
                'time_zone' => 'America/Manaus',
                'participant_info' => '',
                'closed' => 0,
                'max_participants' => null,
                'uid' => 'event-tz-manaus-20260918',
                'rrule' => null,
                'parent_event_id' => null,
                'recurrence_id' => null,
                'exdate' => null,
                'sequence' => 0,
                'location' => '',
            ],
            [],
        );
        $entryManaus->time_zone = 'America/Manaus';
        $entryManaus->save();

        $jwtKey = AuthTokenService::instance()->iCalEncrypt($user->id, $user->guid, false);

        $I->amOnRoute('/calendar/export/calendar', ['token' => $jwtKey]);
        $I->seeResponseCodeIs(200);

        $icsContent = $I->grabResponse();
        $vcalendar = Reader::read($icsContent);

        $declaredTzids = [];
        foreach ($vcalendar->select('VTIMEZONE') as $vtimezone) {
            $declaredTzids[] = (string) $vtimezone->TZID;
        }

        $referencedTzids = [];
        foreach ($vcalendar->VEVENT as $vevent) {
            foreach (['DTSTART', 'DTEND'] as $propName) {
                if (isset($vevent->{$propName}) && isset($vevent->{$propName}['TZID'])) {
                    $referencedTzids[] = (string) $vevent->{$propName}['TZID'];
                }
            }
        }
        $referencedTzids = array_unique($referencedTzids);

        $I->assertContains('Europe/Berlin', $referencedTzids, 'Sanity check: Berlin event uses its own timezone');
        $I->assertContains('America/Manaus', $referencedTzids, 'Sanity check: Manaus event uses its own timezone');

        foreach ($referencedTzids as $tzid) {
            $I->assertContains(
                $tzid,
                $declaredTzids,
                "TZID=$tzid is referenced by a DTSTART/DTEND but has no matching VTIMEZONE component (regression of humhub/humhub#8478)",
            );
        }

        $entryBerlin->hardDelete();
        $entryManaus->hardDelete();
    }

    /**
     * Regression test for https://github.com/humhub/humhub/issues/8478.
     *
     * The actual root cause behind the reported "Imported 0 out of 0 events" failure
     * (confirmed by the reporter against a real Google Calendar import): iCalendar
     * UTC-OFFSET values must always be exactly 4 digits after the sign, e.g. "-0500"
     * (RFC 5545 3.3.14 / 3.2.19). For DST-observing negative-offset zones such as
     * America/Chicago, VTIMEZONE transition entries used to be formatted as "-500" /
     * "-600" (missing leading zero) instead of "-0500" / "-0600" - Google Calendar's
     * importer rejects the whole file on a malformed UTC-OFFSET like that instead of
     * just skipping the offending value.
     */
    public function testIcalExportUtcOffsetsAreZeroPadded(FunctionalTester $I)
    {
        $I->amAdmin();
        $user = User::findOne(['id' => 1]);

        $user->moduleManager->enable('calendar');
        $user->moduleManager->flushCache();
        Yii::$app->moduleManager->flushCache();

        $entry = $I->createCalendarEntry(
            $user,
            [
                'title' => 'Chicago Event',
                'description' => 'Regression test event',
                'start_datetime' => '2026-09-17 10:00:00',
                'end_datetime' => '2026-09-17 11:00:00',
                'all_day' => 0,
                'participation_mode' => 0,
                'color' => '#6f42c1',
                'allow_decline' => 0,
                'allow_maybe' => 0,
                'time_zone' => 'America/Chicago',
                'participant_info' => '',
                'closed' => 0,
                'max_participants' => null,
                'uid' => 'event-tz-chicago-20260917',
                'rrule' => null,
                'parent_event_id' => null,
                'recurrence_id' => null,
                'exdate' => null,
                'sequence' => 0,
                'location' => '',
            ],
            [],
        );
        $entry->time_zone = 'America/Chicago';
        $entry->save();

        $jwtKey = AuthTokenService::instance()->iCalEncrypt($user->id, $user->guid, false);

        $I->amOnRoute('/calendar/export/calendar', ['token' => $jwtKey]);
        $I->seeResponseCodeIs(200);

        $icsContent = $I->grabResponse();

        $I->assertStringContainsString('TZID:America/Chicago', $icsContent, 'America/Chicago VTIMEZONE block is present');

        preg_match_all('/^TZOFFSET(?:FROM|TO):(.+)$/m', $icsContent, $matches);
        $I->assertNotEmpty($matches[1], 'ics contains TZOFFSETFROM/TZOFFSETTO values to check');

        foreach ($matches[1] as $offset) {
            $offset = trim($offset);
            $I->assertTrue(
                (bool) preg_match('/^[+-]\d{4}$/', $offset),
                "TZOFFSET value '$offset' must be exactly 4 digits after the sign (regression of humhub/humhub#8478)",
            );
        }

        $entry->hardDelete();
    }
}
