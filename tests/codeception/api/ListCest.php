<?php

namespace calendar\api;

use calendar\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class ListCest extends HumHubApiTestCest
{
    public function testEmptyList(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('see empty calendar list');
        $I->amAdmin();
        $I->seePaginationCalendarEntriesResponse('calendar', []);
    }

    public function testFilledList(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('see sample created calendar events list');
        $I->amAdmin();
        $I->createCalendarEntry('First event', 'Sample description for the first calendar event.');
        $I->createCalendarEntry('Second event', 'Sample description for the second calendar event.');
        $I->createCalendarEntry('Third event', 'Sample description for the third calendar event.');
        $I->createCalendarEntry('Fourth event', 'Sample description for the fourth calendar event.');
        $I->seePaginationCalendarEntriesResponse('calendar', [1, 2, 3, 4]);
    }

    public function testListByContainer(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('see calendar events by container');
        $I->amAdmin();
        $I->sendGet('calendar/container/123');
        $I->seeNotFoundMessage('Content container not found!');

        $I->createCalendarEntry('Sample calendar event title 1', 'Sample calendar event content 1', ['containerId' => 1]);
        $I->createCalendarEntry('Sample calendar event title 2', 'Sample calendar event content 2', ['containerId' => 4]);
        $I->createCalendarEntry('Sample calendar event title 3', 'Sample calendar event content 3', ['containerId' => 6]);
        $I->createCalendarEntry('Sample calendar event title 4', 'Sample calendar event content 4', ['containerId' => 4]);
        $I->createCalendarEntry('Sample calendar event title 5', 'Sample calendar event content 5', ['containerId' => 7]);
        $I->createCalendarEntry('Sample calendar event title 6', 'Sample calendar event content 6', ['containerId' => 4]);

        $I->seePaginationCalendarEntriesResponse('calendar/container/1', [1]);
        $I->seePaginationCalendarEntriesResponse('calendar/container/4', [2, 4, 6]);
        $I->seePaginationCalendarEntriesResponse('calendar/container/6', [3]);
        $I->seePaginationCalendarEntriesResponse('calendar/container/7', [5]);
    }

    public function testDeleteByContainer(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('delete calendar events by container');
        $I->amAdmin();

        $I->createCalendarEntry('Sample calendar event title 1', 'Sample calendar event content 1', ['containerId' => 4]);
        $I->createCalendarEntry('Sample calendar event title 2', 'Sample calendar event content 2', ['containerId' => 4]);
        $I->createCalendarEntry('Sample calendar event title 3', 'Sample calendar event content 3', ['containerId' => 4]);

        $I->seePaginationCalendarEntriesResponse('calendar/container/4', [1, 2, 3]);
        $I->sendDelete('calendar/container/4');
        $I->seeSuccessMessage('3 records successfully deleted!');
        $I->seePaginationCalendarEntriesResponse('calendar/container/4', []);
    }
}
