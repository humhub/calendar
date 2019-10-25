<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\CalendarUnitTest;
use humhub\modules\calendar\Events;
use humhub\modules\calendar\models\CalendarEntry;
use tests\codeception\_support\HumHubDbTestCase;

class ReminderTest  extends CalendarUnitTest
{
    public function testSentReminder()
    {
        $entry = $this->createEntry();
        Events::onHourlyCron();

    }
}