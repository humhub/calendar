<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\tests\codeception\unit\entry;

use calendar\CalendarUnitTest;
use DateInterval;
use DateTime;
use humhub\modules\calendar\interfaces\event\AbstractCalendarQuery;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\space\models\Space;

class CalendarDashboardFilterWithoutParticipationTest extends CalendarUnitTest
{
    public function testDashboardFilterWithoutParticipationSupportReturnsEntries()
    {
        $this->becomeUser('Admin');
        $space = Space::findOne(['id' => 1]);

        $entry = $this->createEntry((new DateTime())->add(new DateInterval('P2D')), 2, 'Dashboard Entry', $space);

        // Simulates a query without participation support (e.g. External Calendar):
        // it does not implement filterOrIsParticipant() and uses the base behavior.
        $queryClass = (new class extends CalendarEntryQuery {
            public function filterOrIsParticipant()
            {
                AbstractCalendarQuery::filterOrIsParticipant();
            }
        })::class;

        $entries = $queryClass::find()
            ->from(-5)->to(13)
            ->filter([AbstractCalendarQuery::FILTER_DASHBOARD])
            ->all();

        $this->assertCount(1, $entries);
        $this->assertEquals($entry->title, $entries[0]->title);
    }
}
