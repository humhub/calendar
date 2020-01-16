<?php

namespace humhub\modules\calendar\tests\codeception\unit\participation;

use calendar\CalendarUnitTest;
use DateInterval;
use DateTime;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\space\models\Space;

class ParticipationTest extends CalendarUnitTest
{
    public function testDefault()
    {
        $this->becomeUser('admin');
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  Space::findOne(['id' => 1]));
        $this->assertNotEmpty($entry->participation);
        $this->assertEquals(CalendarEntryParticipation::PARTICIPATION_MODE_ALL, $entry->participation_mode);
    }

    public function testGetGuestParticipation()
    {
        $this->becomeUser('admin');
        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  Space::findOne(['id' => 1]));
        $participationOfGuest = $entry->getParticipationStatus(null);
        $this->assertEquals($participationOfGuest, CalendarEntryParticipation::PARTICIPATION_STATUS_NONE);
    }
}