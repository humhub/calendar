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
    // Event 1: Team Meeting (calendar_entry_id=1)
    [
        'id' => 1,
        'calendar_entry_id' => 1,
        'user_id' => 1, // Admin
        'participation_state' => 3,
    ],
    [
        'id' => 2,
        'calendar_entry_id' => 1,
        'user_id' => 2, // User1
        'participation_state' => 3,
    ],
    [
        'id' => 3,
        'calendar_entry_id' => 1,
        'user_id' => 3, // User2
        'participation_state' => 3,
    ],
    [
        'id' => 4,
        'calendar_entry_id' => 1,
        'user_id' => 4, // User3
        'participation_state' => 3,
    ],
    // Event 2: Recurring Team Meeting (calendar_entry_id=2)
    [
        'id' => 5,
        'calendar_entry_id' => 2,
        'user_id' => 1, // Admin
        'participation_state' => 3,
    ],
    [
        'id' => 6,
        'calendar_entry_id' => 2,
        'user_id' => 2, // User1
        'participation_state' => 3,
    ],
    [
        'id' => 7,
        'calendar_entry_id' => 2,
        'user_id' => 3, // User2
        'participation_state' => 3,
    ],
    // Event 3: Event with special characters (calendar_entry_id=3)
    [
        'id' => 8,
        'calendar_entry_id' => 3,
        'user_id' => 2, // User1
        'participation_state' => 3,
    ],
    [
        'id' => 9,
        'calendar_entry_id' => 3,
        'user_id' => 3, // User2
        'participation_state' => 3,
    ],
    [
        'id' => 10,
        'calendar_entry_id' => 3,
        'user_id' => 4, // User3
        'participation_state' => 3,
    ],
];
