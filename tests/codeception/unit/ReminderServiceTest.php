<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\CalendarUnitTest;
use DateInterval;
use DateTime;
use humhub\modules\calendar\Events;
use humhub\modules\calendar\models\CalendarReminder;

class ReminderServiceTest  extends CalendarUnitTest
{
    public function testGlobalReminder()
    {
        Events::onBeforeRequest();

        $this->becomeUser('admin');
        // Global reminder
        $this->assertTrue((new CalendarReminder([
            'unit' => CalendarReminder::UNIT_DAY,
            'value' => 1
        ]))->save());

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('P1D')), 1, 'Test');
        Events::onHourlyCron();
    }
}