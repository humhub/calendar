<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav\enum;

enum EventProperty: string
{
    case TITLE = 'SUMMARY';
    case DESCRIPTION = 'DESCRIPTION';
    case START_DATE = 'DTSTART';
    case END_DATE = 'DTEND';
    case LOCATION = 'LOCATION';
    case UID = 'UID';
    case VISIBILITY = 'CLASS';
    case ATTENDEES = 'ATTENDEE';
    case RECURRENCE = 'RRULE';
    case CATEGORIES = 'CATEGORIES';
}
