<?php

namespace calendar\_support\trait;

trait CalendarEntryHelper
{
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
}