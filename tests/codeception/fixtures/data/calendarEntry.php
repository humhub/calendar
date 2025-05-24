<?php

/**
 * HumHub
 * Copyright © 2025 The HumHub Project
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 */
return [
    // Regular event with participants
    [
        'id' => 1,
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
    // Recurring event (weekly)
    [
        'id' => 2,
        'title' => 'Team Meeting',
        'description' => 'Weekly team sync-up',
        'start_datetime' => '2025-06-08 10:00:00',
        'end_datetime' => '2025-06-08 11:00:00',
        'all_day' => 0,
        'participation_mode' => 2,
        'color' => '#007bff',
        'allow_decline' => 1,
        'allow_maybe' => 1,
        'time_zone' => 'Asia/Yerevan',
        'participant_info' => '',
        'closed' => 0,
        'max_participants' => null,
        'uid' => 'event-001-20250601',
        'rrule' => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=SU',
        'parent_event_id' => 1,
        'recurrence_id' => '20250608T100000',
        'exdate' => null,
        'sequence' => 0,
        'location' => 'Conference Room A',
    ],
    // Event with special characters
    [
        'id' => 3,
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
    // All-day event
    [
        'id' => 4,
        'title' => 'Company Holiday',
        'description' => 'Annual company-wide holiday',
        'start_datetime' => '2025-06-03 00:00:00',
        'end_datetime' => '2025-06-03 23:59:59',
        'all_day' => 1,
        'participation_mode' => 0, // No participation
        'color' => '#dc3545',
        'allow_decline' => 0,
        'allow_maybe' => 0,
        'time_zone' => 'Asia/Yerevan',
        'participant_info' => '',
        'closed' => 0,
        'max_participants' => null,
        'uid' => 'event-003-20250603',
        'rrule' => null,
        'parent_event_id' => null,
        'recurrence_id' => null,
        'exdate' => null,
        'sequence' => 0,
        'location' => null,
    ],
];
