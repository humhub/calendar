<?php

namespace calendar\api;

use calendar\ApiTester;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use tests\codeception\_support\HumHubApiTestCest;
use yii\web\UploadedFile;

class EventCest extends HumHubApiTestCest
{
    public function testCreateEvent(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('create a calendar event');
        $I->amAdmin();
        $I->createSampleCalendarEntry();
        $I->seeLastCreatedCalendarEntryDefinition();

        $I->amGoingTo('create a calendar event with error');
        $I->sendPost('calendar/container/1');
        $I->seeServerErrorMessage('Internal error while save calendar entry!');
    }

    public function testGetEventById(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('see calendar event by id');
        $I->amAdmin();
        $I->createSampleCalendarEntry();
        $I->sendGet('calendar/entry/1');
        $I->seeCalendarEntryDefinitionById(1);
    }

    public function testUpdateEventById(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('update calendar event by id');
        $I->amAdmin();

        $I->sendPut('calendar/entry/1');
        $I->seeNotFoundMessage('Calendar entry not found!');

        $I->createSampleCalendarEntry();
        $I->sendPut('calendar/entry/1', [
            'CalendarEntry' => [
                'title' => 'Updated event title',
                'description' => 'Updated event description',
            ],
            'CalendarEntryForm' => [
                'start_date' => '2021-04-26',
                'start_time' => '10:30',
                'end_date' => '2021-04-26',
                'end_time' => '19:30',
                'reminder' => 1,
                'recurring' => 1,
            ],
        ]);
        $I->seeCalendarEntryDefinitionById(1);
    }

    public function testDeleteEventById(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('delete calendar event by id');
        $I->amAdmin();

        $I->sendDelete('calendar/entry/1');
        $I->seeNotFoundMessage('Content record not found!');

        $I->createSampleCalendarEntry();
        $I->sendDelete('calendar/entry/1');
        $I->seeSuccessMessage('Successfully deleted!');
    }

    public function testChangeEventParticipation(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('change event participation');
        $I->amAdmin();

        $I->createSampleCalendarEntry();
        $I->sendPost('calendar/entry/1/respond', ['type' => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE]);
        $I->seeSuccessMessage('Participation successfully changed.');
    }

    public function testEventFiles(ApiTester $I)
    {
        if (!$this->isRestModuleEnabled()) {
            return;
        }

        $I->wantTo('upload/remove files to the event');
        $I->amAdmin();

        $I->createSampleCalendarEntry();
        $I->sendPost('calendar/entry/1/upload-files');
        $I->seeBadMessage('No files to upload.');
        UploadedFile::reset();

        $I->sendPost('calendar/entry/1/upload-files', [], [
            'files' => [
                codecept_data_dir('test1.txt'),
                codecept_data_dir('test2.txt'),
            ],
        ]);
        $I->seeSuccessMessage('Files successfully uploaded.');

        $I->amGoingTo('remove a file from the event');
        $I->sendDelete('calendar/entry/1/remove-file/2');
        $I->seeSuccessMessage('File successfully removed.');

        $I->sendDelete('calendar/entry/1/remove-file/2');
        $I->seeNotFoundMessage('Could not find requested content record or file!');
    }

}
