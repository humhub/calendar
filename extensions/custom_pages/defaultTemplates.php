<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\extensions\custom_pages\elements\CalendarEventsElement;
use humhub\modules\custom_pages\modules\template\models\Template;

return [
    'calendar_events' => [
        'type' => Template::TYPE_LAYOUT,
        'description' => 'Default template for calendar events.',
        'source' => '<ul class="calendar-events">
{% for event in events.items %}
    <li class="calendar-event">{{ event.title }} - {{ event.start_datetime }}</li>
{% endfor %}
</ul>',
        'elements' => [
            [
                'name' => 'events',
                'content_type' => CalendarEventsElement::class,
                'dyn_attributes' => [
                    'type' => 1,
                    'space' => '',
                    'author' => '',
                    'topic' => '',
                    'filter' => [
                        'participant',
                    ],
                    'limit' => 10,
                    'nextDays' => 7,
                    'sortOrder' => 'date_old',
                ],
            ],
        ],
    ],
];
