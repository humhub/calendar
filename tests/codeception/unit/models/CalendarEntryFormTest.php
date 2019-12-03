<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\tests\codeception\unit\models;

use calendar\CalendarUnitTest;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 16.07.2017
 * Time: 20:52
 */
class CalendarEntryFormTest extends CalendarUnitTest
{
    public function _createCalendarForm() {

        $space1 = Space::findOne(['id' => 1]);

        $calendarForm = CalendarEntryForm::createEntry($space1);

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

    public function _before()
    {
        parent::_before();

        $this->becomeUser('User2');
        Yii::$app->user->identity->updateAttributes(['time_zone' => 'Europe/Berlin']);
        Yii::$app->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->locale = 'de';
        CalendarUtils::flush();
    }

    public function testCreateAllDay()
    {
        $this->becomeUser('User2');
        $space1 = Space::findOne(['id' => 1]);

        $calendarForm = CalendarEntryForm::createEntry($space1, '2019-11-13 00:00:00', '2019-11-14 00:00:00');
        $entry = $calendarForm->entry;
        $this->assertTrue($calendarForm->entry->isAllDay());
        $this->assertEquals(1, $calendarForm->entry->allow_maybe);
        $this->assertEquals(1, $calendarForm->entry->allow_decline);
        $this->assertEquals(CalendarEntryParticipation::PARTICIPATION_MODE_ALL, $calendarForm->entry->participation_mode);
        $this->assertEquals('2019-11-13', $calendarForm->start_date);
        $this->assertEquals('00:00', $calendarForm->start_time);
        $this->assertEquals('2019-11-13', $calendarForm->end_date);
        $this->assertEquals('23:59', $calendarForm->end_time);
        $this->assertEquals(CalendarUtils::getUserTimeZone(true), $entry->time_zone);

        $calendarForm->entry->title = 'My Test Calendar Entry';

        $this->assertTrue($calendarForm->save());
        $this->assertEquals('2019-11-13 00:00:00', $entry->start_datetime);
        $this->assertEquals('2019-11-13 23:59:59', $entry->end_datetime);
    }

    public function testLegacyAllDayEvent()
    {
        Yii::$app->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->locale = 'de';

        $this->becomeUser('User2');
        $space1 = Space::findOne(['id' => 1]);
        $calendarForm = CalendarEntryForm::createEntry($space1, '2019-11-13 00:00:00', '2019-11-14 00:00:00');
        $calendarForm->entry->title = 'Legacy Event';
        $this->assertTrue($calendarForm->save());

        // Save legacy all day
        $calendarForm->entry->updateAttributes([
            'start_datetime' => '2019-11-12 23:00:00', // Should be 2019-11-13 00:00:00
            'end_datetime' => '2019-11-13 22:59:00', // Should be 2019-11-13 23:59:59
            'time_zone' => 'Europe/Bucharest',
            'all_day' => 1
        ]);

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('2019-11-13', $calendarForm->start_date);
        $this->assertEquals('00:00', $calendarForm->start_time);
        $this->assertEquals('2019-11-13', $calendarForm->end_date);
        $this->assertEquals('23:59', $calendarForm->end_time);
        $this->assertEquals('2019-11-13 00:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2019-11-13 23:59:59', $calendarForm->entry->end_datetime);

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);
        $this->assertEquals('2019-11-13 00:00:00', $entry->start_datetime);
        $this->assertEquals('2019-11-13 23:59:59', $entry->end_datetime);

    }

    public function testLoadAllDayFromEntry()
    {
        $this->becomeUser('User2');
        $space1 = Space::findOne(['id' => 1]);
        $calendarForm = CalendarEntryForm::createEntry($space1, '2019-11-13 00:00:00', '2019-11-14 00:00:00');
        $calendarForm->entry->title = 'Test';
        $this->assertTrue($calendarForm->save());

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);

        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertTrue($calendarForm->entry->isAllDay());
        $this->assertEquals(1, $calendarForm->entry->allow_maybe);
        $this->assertEquals(1, $calendarForm->entry->allow_decline);
        $this->assertEquals(CalendarEntryParticipation::PARTICIPATION_MODE_ALL, $calendarForm->entry->participation_mode);
        $this->assertEquals('2019-11-13', $calendarForm->start_date);
        $this->assertEquals('00:00', $calendarForm->start_time);
        $this->assertEquals('2019-11-13', $calendarForm->end_date);
        $this->assertEquals('23:59', $calendarForm->end_time);
        $this->assertEquals(CalendarUtils::getUserTimeZone(true), $entry->time_zone);
    }

    public function testCreateNonAllDay()
    {
        $space1 = Space::findOne(['id' => 1]);

        $calendarForm = CalendarEntryForm::createEntry($space1, '2019-11-13 13:00:00', '2019-11-14 12:00:00');
        $entry = $calendarForm->entry;
        $this->assertFalse($calendarForm->entry->isAllDay());
        $this->assertEquals(1, $calendarForm->entry->allow_maybe);
        $this->assertEquals(1, $calendarForm->entry->allow_decline);
        $this->assertEquals(CalendarEntryParticipation::PARTICIPATION_MODE_ALL, $calendarForm->entry->participation_mode);
        $this->assertEquals('Europe/Berlin', $entry->time_zone);
        $this->assertEquals('2019-11-13 13:00:00', $entry->start_datetime);
        $this->assertEquals('2019-11-14 12:00:00', $entry->end_datetime);
    }

    public function testCreateNonAllDayWithDifferentUserTZ()
    {
        Yii::$app->user->identity->updateAttributes(['time_zone' => 'Europe/Bucharest']);

        $space1 = Space::findOne(['id' => 1]);

        $calendarForm = CalendarEntryForm::createEntry($space1, '2019-11-13 13:00:00', '2019-11-13 15:00:00');
        $entry = $calendarForm->entry;
        $this->assertFalse($calendarForm->entry->isAllDay());
        $this->assertEquals(1, $calendarForm->entry->allow_maybe);
        $this->assertEquals(1, $calendarForm->entry->allow_decline);
        $this->assertEquals(CalendarEntryParticipation::PARTICIPATION_MODE_ALL, $calendarForm->entry->participation_mode);
        $this->assertEquals('Europe/Bucharest', $entry->time_zone);
        $this->assertEquals('Europe/Bucharest', $calendarForm->timeZone);
        $this->assertEquals('2019-11-13 12:00:00', $entry->start_datetime);
        $this->assertEquals('2019-11-13 14:00:00', $entry->end_datetime);
    }

    public function testSubmitFormForNewEvent()
    {
        $space1 = Space::findOne(['id' => 1]);
        $calendarForm = CalendarEntryForm::createEntry($space1);

        $loaded = $calendarForm->load([
            'CalendarEntry' => [
                'title' => 'Test title',
                'description' => 'TestDescription',
                'participation_mode' => 2
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'all_day' => '0',
                'start_date' => '2019-11-13',
                'start_time' => '12:00',
                'end_date' => '2019-11-13',
                'end_time' => '15:00'
            ]
        ]);

        $this->assertTrue($loaded);
        $this->assertTrue($calendarForm->save());
        $this->assertEquals('2019-11-13', $calendarForm->start_date);
        $this->assertEquals('12:00', $calendarForm->start_time);
        $this->assertEquals('2019-11-13', $calendarForm->end_date);
        $this->assertEquals('15:00', $calendarForm->end_time);
    }

    public function testSubmitFormForNewEventDateFormat()
    {
        Yii::$app->formatter->locale = 'en-US';

        $space1 = Space::findOne(['id' => 1]);
        $calendarForm = CalendarEntryForm::createEntry($space1, '2019-11-13 13:00:00', '2019-11-13 15:00:00');

        $loaded = $calendarForm->load([
            'CalendarEntry' => [
                'title' => 'Test title',
                'description' => 'TestDescription',
                'participation_mode' => 2
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'all_day' => '0',
                'start_date' => '11/11/19',
                'start_time' => '12:00 AM',
                'end_date' => '11/11/19',
                'end_time' => '3:00 PM'
            ]
        ]);

        $this->assertTrue($loaded);
        $this->assertTrue($calendarForm->save());
        $this->assertEquals('2019-11-11', $calendarForm->start_date);
        $this->assertEquals('12:00 AM', $calendarForm->start_time);
        $this->assertEquals('2019-11-11', $calendarForm->end_date);
        $this->assertEquals('03:00 PM', $calendarForm->end_time);
        $this->assertEquals('2019-11-11 00:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2019-11-11 15:00:00', $calendarForm->entry->end_datetime);
    }

    public function testSave()
    {
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
        // System TZ = Europe/Berlin -> UTC + 2
        $this->assertEquals('Europe/Berlin', CalendarUtils::getSystemTimeZone(true));

        $this->becomeUser('Admin');

        // Set User TZ to = Europe/London -> UTC + 1
        Yii::$app->user->getIdentity()->time_zone = 'Europe/London';

        $calendarForm = $this->_createCalendarForm();

        $entry = CalendarEntry::findOne(['id' => $calendarForm->entry->id]);

        Yii::$app->user->getIdentity()->time_zone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';

        $calendarForm = new CalendarEntryForm(['entry' => $entry]);
        $this->assertEquals('2017-06-27', $calendarForm->start_date);
        $this->assertEquals('12:00', $calendarForm->start_time);
        $this->assertEquals('2017-06-28', $calendarForm->end_date);
        $this->assertEquals('13:00', $calendarForm->end_time);

        $this->assertEquals('2017-06-27 13:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2017-06-28 14:00:00', $calendarForm->entry->end_datetime);


        // Change timezone of event to Europe/Sofia -> UTC +3
        $this->assertTrue($calendarForm->load([
            'CalendarEntry' => [],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'all_day' => '0',
                'timeZone' => 'Europe/Sofia',
                'start_date' => '6/27/17',
                'start_time' => '12:00 PM',
                'end_date' => '6/28/17',
                'end_time' => '01:00 PM'
            ]
        ]));

        $this->assertTrue($calendarForm->save());

        // Reload form
        $calendarForm = new CalendarEntryForm(['entry' => $entry]);

        $this->assertEquals('2017-06-27', $calendarForm->start_date);
        $this->assertEquals('12:00', $calendarForm->start_time);
        $this->assertEquals('2017-06-28', $calendarForm->end_date);
        $this->assertEquals('13:00', $calendarForm->end_time);
        $this->assertEquals('Europe/Sofia', $calendarForm->timeZone);

        $this->assertEquals('Europe/Sofia', $calendarForm->entry->time_zone);
        $this->assertEquals('2017-06-27 11:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2017-06-28 12:00:00', $calendarForm->entry->end_datetime);
    }

    public function testAllDayTimeZone()
    {
        // System TZ = Europe/Berlin -> UTC + 2
        $this->assertEquals('Europe/Berlin', CalendarUtils::getSystemTimeZone(true));

        $this->becomeUser('Admin');

        // Set User TZ to = Europe/London -> UTC + 1
        Yii::$app->user->getIdentity()->time_zone = 'Europe/London';

        $space1 = Space::findOne(['id' => 1]);

        $calendarForm = CalendarEntryForm::createEntry($space1);
        $this->assertEquals('Europe/London', $calendarForm->timeZone);

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
        $this->assertEquals('Europe/London', $calendarForm->timeZone);
        $this->assertEquals('2017-06-27', $calendarForm->start_date);
        $this->assertEquals('00:00', $calendarForm->start_time);
        $this->assertEquals('2017-06-27', $calendarForm->end_date);
        $this->assertEquals('23:59', $calendarForm->end_time);

        // Timezone is ignored for all day events
        $this->assertEquals('2017-06-27 00:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2017-06-27 23:59:59', $calendarForm->entry->end_datetime);
    }

    public function testCreateAllDayTimeZone()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->getIdentity()->time_zone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';

        $space1 = Space::findOne(['id' => 1]);

        $calendarForm = CalendarEntryForm::createEntry($space1, '2017-08-16 00:00:00', '2017-08-17 00:00:00');
        $this->assertEquals('Europe/Berlin', $calendarForm->timeZone);
        // This is how a full day is given in fullcalendar
        $this->assertEquals('Europe/Berlin', $calendarForm->timeZone);
        $this->assertEquals('2017-08-16', $calendarForm->start_date);
        $this->assertEquals('00:00', $calendarForm->start_time);
        $this->assertEquals('2017-08-16', $calendarForm->end_date);
        $this->assertEquals('23:59', $calendarForm->end_time);

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
        $this->assertEquals('2017-08-16', $calendarForm->start_date);
        $this->assertEquals('00:00', $calendarForm->start_time);
        $this->assertEquals('2017-08-16', $calendarForm->end_date);
        $this->assertEquals('23:59', $calendarForm->end_time);
    }
}