<?php

namespace calendar;

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

    /**
     * Define custom actions here
     */

    public function createCalendarEntry($title, $description, $params = [])
    {
        $params = array_merge([
            'containerId' => 1,
            'color' => '#6fdbe8',
            'all_day' => 1,
            'participation_mode' => 2,
            'max_participants' => '',
            'allow_decline' => 1,
            'allow_maybe' => 1,
            'participant_info' => '',
            'is_public' => 0,
            'start_date' => '2021-03-26',
            'start_time' => '9:00',
            'end_date' => '2021-03-26',
            'end_time' => '18:00',
            'timeZone' => 'Europe/Helsinki',
            'forceJoin' => 1,
            'topics' => '',
            'reminder' => 1,
            'recurring' => 1,
        ], $params);

        $this->amGoingTo('create a sample calendar entry');
        $this->sendPost('calendar/container/' . $params['containerId'], [
            'CalendarEntry' => [
                'title' => $title,
                'description' => $description,
                'color' => $params['color'],
                'all_day' => $params['all_day'],
                'participation_mode' => $params['participation_mode'],
                'max_participants' => $params['max_participants'],
                'allow_decline' => $params['allow_decline'],
                'allow_maybe' => $params['allow_maybe'],
                'participant_info' => $params['participant_info'],
            ],
            'CalendarEntryForm' => [
                'is_public' => $params['is_public'],
                'start_date' => $params['start_date'],
                'start_time' => $params['start_time'],
                'end_date' => $params['end_date'],
                'end_time' => $params['end_time'],
                'timeZone' => $params['timeZone'],
                'forceJoin' => $params['forceJoin'],
                'topics' => $params['topics'],
                'reminder' => $params['reminder'],
                'recurring' => $params['recurring'],
            ],
        ]);
    }

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
