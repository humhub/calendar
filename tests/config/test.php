<?php

return [
    'modules' => ['calendar'],
    'humhub_root' => '/app/humhub',
    'fixtures' => [
        'default',
        'calendar_entry' => 'humhub\modules\calendar\tests\codeception\fixtures\CalendarEntryFixture',
    ],
];
