<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\tests\codeception\unit\models;

use calendar\CalendarUnitTest;
use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 16.07.2017
 * Time: 20:52
 */
class CalendarUtilTest extends CalendarUnitTest
{
    public function testTranslateTimezoneFromString()
    {
        $date = '2019-11-13 01:00:00';
        $translated = CalendarUtils::translateTimezone($date, 'UTC', 'Europe/Berlin');
        $this->assertEquals('2019-11-13 02:00:00', $translated);
    }

    /**
     * This test makes sure the translateTimezone function ignores given timezone information of the date
     * @throws \Exception
     */
    public function testTranslateTimezoneFromStringWithGivenTimezone()
    {
        $date = '2019-11-13 01:00:00 Europe/Berlin';
        $translated = CalendarUtils::translateTimezone($date, 'UTC', 'Europe/Berlin');
        $this->assertEquals('2019-11-13 02:00:00', $translated);
    }

    public function testTranslateTimezoneFromDateTime()
    {
        $date = new DateTime('2019-11-13 01:00:00');
        $translated = CalendarUtils::translateTimezone($date, 'UTC', 'Europe/Berlin');
        $this->assertEquals('2019-11-13 02:00:00', $translated);
    }

    /**
     * This test makes sure the translateTimezone function ignores given timezone information of the date
     * @throws \Exception
     */
    public function testTranslateTimezoneFromDateTimeWithGivenTimezone()
    {
        $date = new DateTime('2019-11-13 01:00:00', new \DateTimeZone('Europe/Berlin'));
        $translated = CalendarUtils::translateTimezone($date, 'UTC', 'Europe/Berlin');
        $this->assertEquals('2019-11-13 02:00:00', $translated);
    }

    /**
     * @throws \Exception
     */
    public function testTranslateToSystemTimezone()
    {
        Yii::$app->timeZone = 'UTC';
        $date = new DateTime('2019-11-13 02:00:00');
        $translated = CalendarUtils::translateToSystemTimezone($date, 'Europe/Berlin');
        $this->assertEquals('2019-11-13 01:00:00', $translated);
    }

    public function testTranslateToUserTimezoneFromSystem()
    {
        $this->becomeUser('User1');
        Yii::$app->timeZone = 'UTC';
        Yii::$app->user->identity->time_zone = 'Europe/Berlin';

        $date = new DateTime('2019-11-13 01:00:00');
        $translated = CalendarUtils::translateToUserTimezone($date);
        $this->assertEquals('2019-11-13 02:00:00', $translated);
    }

    public function testTranslateToUserTimezoneFromCustom()
    {
        $this->becomeUser('User1');
        Yii::$app->timeZone = 'UTC';
        Yii::$app->user->identity->time_zone = 'Europe/Berlin';

        $date = new DateTime('2019-11-13 01:00:00');
        $translated = CalendarUtils::translateToUserTimezone($date, 'Europe/Berlin');
        $this->assertEquals('2019-11-13 01:00:00', $translated);
    }

    public function testFullDayNonStrictMomentAfter()
    {
        $start = new DateTime('2019-11-13 00:00:00');
        $end = new DateTime('2019-11-14 00:00:00');
        $this->assertTrue(CalendarUtils::isAllDay($start, $end));
    }

    public function testFullDayNonStrictMomentAfterFails()
    {
        $start = new DateTime('2019-11-13 01:00:00');
        $end = new DateTime('2019-11-14 00:00:00');
        $this->assertFalse(CalendarUtils::isAllDay($start, $end));
    }

    public function testFullDayNonStrictMomentAfterFails2()
    {
        $start = new DateTime('2019-11-13 00:00:00');
        $end = new DateTime('2019-11-14 01:00:00');
        $this->assertFalse(CalendarUtils::isAllDay($start, $end));
    }

    public function testFullDayNonStrictEqualDatesFails()
    {
        $start = new DateTime('2019-11-13 00:00:00');
        $end = new DateTime('2019-11-13 00:00:00');
        $this->assertFalse(CalendarUtils::isAllDay($start, $end));
    }

    public function testFullDayNonStrictNonMomentAfter()
    {
        $start = new DateTime('2019-11-13 00:00:00');
        $end = new DateTime('2019-11-14 23:59:59');
        $this->assertTrue(CalendarUtils::isAllDay($start, $end));
    }

    public function testFullDayStrictMomentAfter()
    {
        $start = new DateTime('2019-11-13 00:00:00');
        $end = new DateTime('2019-11-14 00:00:00');
        $this->assertTrue(CalendarUtils::isAllDay($start, $end, true));
    }

    public function testFullDayStrictMomentAfterFails()
    {
        $start = new DateTime('2019-11-13 00:00:00');
        $end = new DateTime('2019-11-14 23:59:59');
        $this->assertFalse(CalendarUtils::isAllDay($start, $end, true));
    }

    public function testFullDayStrictNonMomentAfterFails()
    {
        $start = new DateTime('2019-11-13 00:00:00');
        $end = new DateTime('2019-11-14 00:00:00');
        $this->assertFalse(CalendarUtils::isAllDay($start, $end, false));
    }

    public function testFullDayStrictNonMomentAfter()
    {
        $start = new DateTime('2019-11-13 00:00:00');
        $end = new DateTime('2019-11-14 23:59:59');
        $this->assertTrue(CalendarUtils::isAllDay($start, $end, false));
    }
}