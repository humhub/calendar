<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\tests\codeception\unit;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 16.07.2017
 * Time: 20:52
 */
class CalendarEntryFormTest extends HumHubDbTestCase
{
    public function _createCalendarForm()
    {
        $space1 = Space::findOne(['id' => 1]);

        $calendarForm = new CalendarEntryForm();
        $calendarForm->createNew($space1);

        $this->assertTrue($calendarForm->load([
            'CalendarEntry' => [
                'title' => 'Test title',
                'description' => 'TestDescription',
                'participation_mode' => 2
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'all_day' => '0',
                'start_date' => '6/27/17',
                'start_time' => '12:00 PM',
                'end_date' => '6/28/17',
                'end_time' => '01:00 PM'
            ]
        ]));

        $this->assertTrue($calendarForm->save());
        return $calendarForm;
    }

    public function testSave()
    {
        // Admin has timeZone UTC
        $this->becomeUser('Admin');
        $calendarForm = $this->_createCalendarForm();

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $this->assertEquals('Test title', $entry->title);
        $this->assertEquals('TestDescription', $entry->description);
        $this->assertEquals(2, $entry->participation_mode);
        $this->assertEquals(Content::VISIBILITY_PUBLIC, $entry->content->visibility);
        $this->assertEquals(0, $entry->all_day);
        $this->assertEquals('2017-06-27 12:00:00', $entry->start_datetime);
        $this->assertEquals('2017-06-28 13:00:00', $entry->end_datetime);
    }

    public function testTimeZone()
    {
        // Admin has timeZone UTC
        $this->becomeUser('Admin');
        $calendarForm = $this->_createCalendarForm();

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);

        /**
         * Test formatted date for user with another timezone set.
         * Switch user timezone to UTC+02:00 - Europe/Berlin
         */
        Yii::$app->user->getIdentity()->time_zone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';

        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('2017-06-27 14:00:00 Europe/Berlin', $calendarForm->start_date);
        $this->assertEquals('2:00 PM', $calendarForm->start_time);
        $this->assertEquals('2017-06-28 15:00:00 Europe/Berlin', $calendarForm->end_date);
        $this->assertEquals('3:00 PM', $calendarForm->end_time);

        // Load same time data, but with user timeZone
        $this->assertTrue($calendarForm->load([
            'CalendarEntry' => [],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'all_day' => '0',
                'start_date' => '6/27/17',
                'start_time' => '02:00 PM',
                'end_date' => '6/28/17',
                'end_time' => '03:00 PM'
            ]
        ]));

        $this->assertTrue($calendarForm->save());

        // Change back to UTC
        Yii::$app->user->getIdentity()->time_zone = Yii::$app->timeZone;
        Yii::$app->formatter->timeZone = Yii::$app->timeZone;
        Yii::$app->formatter->locale = 'de';

        // Make sure the time values of the actual entry are as before (since we did only change timezone)
        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $this->assertEquals('Test title', $entry->title);
        $this->assertEquals('TestDescription', $entry->description);
        $this->assertEquals(2, $entry->participation_mode);
        $this->assertEquals(Content::VISIBILITY_PUBLIC, $entry->content->visibility);
        $this->assertEquals(0, $entry->all_day);
        $this->assertEquals('2017-06-27 12:00:00', $entry->start_datetime);
        $this->assertEquals('2017-06-28 13:00:00', $entry->end_datetime);

        // Reload form with UTC timeZone and make sure the time values are valid
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('2017-06-27 12:00:00 UTC', $calendarForm->start_date);
        $this->assertEquals('12:00', $calendarForm->start_time);
        $this->assertEquals('2017-06-28 13:00:00 UTC', $calendarForm->end_date);
        $this->assertEquals('13:00', $calendarForm->end_time);
    }

    public function testAllDayTimeZone()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->getIdentity()->time_zone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';

        $space1 = Space::findOne(['id' => 1]);

        $calendarForm = new CalendarEntryForm();
        $this->assertEquals('Europe/Berlin', $calendarForm->timeZone);
        $calendarForm->createNew($space1);

        $this->assertTrue($calendarForm->load([
            'CalendarEntry' => [
                'all_day' => '1',
                'title' => 'Test title',
                'description' => 'TestDescription',
                'participation_mode' => 2
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'start_date' => '6/27/17',
                'end_date' => '6/27/17',
            ]
        ]));

        $this->assertTrue($calendarForm->save());

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('Europe/Berlin', $calendarForm->timeZone);
        $this->assertEquals('2017-06-27 00:00:00 Europe/Berlin', $calendarForm->start_date);
        $this->assertEquals('12:00 AM', $calendarForm->start_time);
        $this->assertEquals('2017-06-27 23:59:00 Europe/Berlin', $calendarForm->end_date);
        $this->assertEquals('11:59 PM', $calendarForm->end_time);
    }

    public function testCreateAllDayTimeZone()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->getIdentity()->time_zone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';

        $space1 = Space::findOne(['id' => 1]);

        $calendarForm = new CalendarEntryForm();
        $this->assertEquals('Europe/Berlin', $calendarForm->timeZone);
        // This is how a full day is given in fullcalendar
        $calendarForm->createNew($space1, '2017-08-16 00:00:00', '2017-08-17 00:00:00');
        $this->assertEquals('Europe/Berlin', $calendarForm->timeZone);
        $this->assertEquals('2017-08-16 00:00:00 Europe/Berlin', $calendarForm->start_date);
        $this->assertEquals('12:00 AM', $calendarForm->start_time);
        $this->assertEquals('2017-08-16 23:59:59 Europe/Berlin', $calendarForm->end_date);
        $this->assertEquals('11:59 PM', $calendarForm->end_time);

        $this->assertTrue($calendarForm->load([
            'CalendarEntry' => [
                'all_day' => '1',
                'title' => 'Test title',
                'description' => 'TestDescription',
                'participation_mode' => 2
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'start_date' => '8/16/17',
                'end_date' => '8/16/17',
            ]
        ]));

        $this->assertTrue($calendarForm->save());

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('Europe/Berlin', $calendarForm->timeZone);
        $this->assertEquals('2017-08-16 00:00:00 Europe/Berlin', $calendarForm->start_date);
        $this->assertEquals('12:00 AM', $calendarForm->start_time);
        $this->assertEquals('2017-08-16 23:59:00 Europe/Berlin', $calendarForm->end_date);
        $this->assertEquals('11:59 PM', $calendarForm->end_time);
    }
}