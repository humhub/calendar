<?php

namespace calendar;

use calendar\_support\trait\CalendarEntryHelper;
use humhub\modules\calendar\helpers\RestDefinitions;
use humhub\modules\calendar\models\CalendarEntry;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class ApiTester extends \ApiTester
{
    use _generated\ApiTesterActions;
    use CalendarEntryHelper;

    /**
     * Define custom actions here
     */

    public function createSampleCalendarEntry()
    {
        $this->createCalendarEntry('Sample calendar event title', 'Sample calendar event content');
    }

    public function getCalendarEntryDefinitionById($calendarEntryId)
    {
        $calendarEntry = CalendarEntry::findOne(['id' => $calendarEntryId]);
        return ($calendarEntry ? RestDefinitions::getCalendarEntry($calendarEntry) : []);
    }

    public function seeLastCreatedCalendarEntryDefinition()
    {
        $calendarEntry = CalendarEntry::find()
            ->orderBy(['id' => SORT_DESC])
            ->one();
        $calendarEntryDefinition = ($calendarEntry ? RestDefinitions::getCalendarEntry($calendarEntry) : []);
        $this->seeSuccessResponseContainsJson($calendarEntryDefinition);
    }

    public function seeCalendarEntryDefinitionById($calendarEntryId)
    {
        $this->seeSuccessResponseContainsJson($this->getCalendarEntryDefinitionById($calendarEntryId));
    }

    public function seePaginationCalendarEntriesResponse($url, $calendarEntryIds)
    {
        $calendarEntryDefinitions = [];
        foreach ($calendarEntryIds as $calendarEntryId) {
            $calendarEntryDefinitions[] = $this->getCalendarEntryDefinitionById($calendarEntryId);
        }

        $this->seePaginationGetResponse($url, $calendarEntryDefinitions);
    }

}
