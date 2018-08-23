<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\tests\codeception\unit;

use humhub\modules\calendar\models\CalendarEntry;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 17.09.2017
 * Time: 20:25
 */

class CalendarFormatTest extends HumHubDbTestCase
{
    public function testAllDayOneDayFormatSameTimezone()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->getIdentity()->time_zone = Yii::$app->timeZone;
        Yii::$app->user->getIdentity()->language = 'de';
        Yii::$app->i18n->autosetLocale();

        $entry = new CalendarEntry([
            'start_datetime' => '2017-09-12 00:00:00',
            'end_datetime' => '2017-09-12 23:59:00',
            'all_day' => 1,
            'time_zone' => Yii::$app->timeZone
        ]);

        $this->assertEquals('12. September 2017', $entry->getFormattedTime());
        $this->assertEquals('12.09.2017', $entry->getFormattedTime('medium'));
    }

    public function testAllDayOneDayFormatDifferentTimezone()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->getIdentity()->time_zone = Yii::$app->timeZone;
        Yii::$app->user->getIdentity()->language = 'de';
        Yii::$app->i18n->autosetLocale();

        $entry = new CalendarEntry([
            'start_datetime' => '2017-09-12 00:00:00',
            'end_datetime' => '2017-09-12 23:59:00',
            'all_day' => 1,
            'time_zone' => 'Europe/Berlin'
        ]);

        $this->assertEquals('12. September 2017 (UTC+02:00 - Europe/Berlin)', $entry->getFormattedTime());
        $this->assertEquals('12.09.2017 (UTC+02:00 - Europe/Berlin)', $entry->getFormattedTime('medium'));
    }

    public function testAllDayOneDayFormatDifferentTimezone2()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->getIdentity()->time_zone = 'America/Miquelon';
        Yii::$app->user->getIdentity()->language = 'de';
        Yii::$app->i18n->autosetLocale();

        $entry = new CalendarEntry([
            'start_datetime' => '2017-09-12 00:00:00',
            'end_datetime' => '2017-09-12 23:59:00',
            'all_day' => 1,
            'time_zone' => Yii::$app->timeZone
        ]);

        // Date is not trnaslated since we use all_day = 1
        $this->assertEquals('12. September 2017 (UTC)', $entry->getFormattedTime());
        $this->assertEquals('12.09.2017 (UTC)', $entry->getFormattedTime('medium'));
    }

    public function testAllDayMultipleDayFormat()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->getIdentity()->time_zone = Yii::$app->timeZone;
        Yii::$app->user->getIdentity()->language = 'de';
        Yii::$app->i18n->autosetLocale();

        $entry = new CalendarEntry([
            'start_datetime' => '2017-09-12 00:00:00',
            'end_datetime' => '2017-09-13 23:59:00',
            'all_day' => 1,
            'time_zone' => Yii::$app->timeZone
        ]);

        // Date is not trnaslated since we use all_day = 1
        $this->assertEquals('12. September 2017 - 13. September 2017', $entry->getFormattedTime());
        $this->assertEquals('12.09.2017 - 13.09.2017', $entry->getFormattedTime('medium'));
    }

    public function testAllDayMultipleDayFormatDifferentTimezone()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->getIdentity()->time_zone = 'America/Miquelon'; // UTC -2:00;
        Yii::$app->user->getIdentity()->language = 'de';
        Yii::$app->i18n->autosetLocale();

        $entry = new CalendarEntry([
            'start_datetime' => '2017-09-12 00:00:00',
            'end_datetime' => '2017-09-13 23:59:00',
            'all_day' => 1,
            'time_zone' => Yii::$app->timeZone
        ]);

        // Date is not trnaslated since we use all_day = 1
        $this->assertEquals('12. September 2017 - 13. September 2017 (UTC)', $entry->getFormattedTime());
        $this->assertEquals('12.09.2017 - 13.09.2017 (UTC)', $entry->getFormattedTime('medium'));
    }

    public function testNonAllDayOneDayFormatDifferentTimezone()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->getIdentity()->time_zone = 'America/Miquelon'; // UTC -2:00;
        Yii::$app->user->getIdentity()->language = 'de';
        Yii::$app->i18n->autosetLocale();

        $entry = new CalendarEntry([
            'start_datetime' => '2017-09-12 00:00:00',
            'end_datetime' => '2017-09-12 12:59:00',
            'all_day' => 0,
            'time_zone' => Yii::$app->timeZone
        ]);

        // Date is not trnaslated since we use all_day = 1
        $this->assertEquals('11. September 2017 (22:00 - 10:59)', $entry->getFormattedTime());
        $this->assertEquals('11.09.2017 (22:00 - 10:59)', $entry->getFormattedTime('medium'));
    }

    public function testNonAllDayMultipleDayFormatDifferentTimezone()
    {
        $this->becomeUser('Admin');
        Yii::$app->user->getIdentity()->time_zone = 'America/Miquelon'; // UTC -2:00;
        Yii::$app->user->getIdentity()->language = 'de';
        Yii::$app->i18n->autosetLocale();

        $entry = new CalendarEntry([
            'start_datetime' => '2017-09-12 00:00:00',
            'end_datetime' => '2017-09-13 12:59:00',
            'all_day' => 0,
            'time_zone' => Yii::$app->timeZone
        ]);

        // Date is not trnaslated since we use all_day = 1
        $this->assertEquals('11. September 2017, 22:00 - 13. September 2017, 10:59', $entry->getFormattedTime());
        $this->assertEquals('11.09.2017, 22:00 - 13.09.2017, 10:59', $entry->getFormattedTime('medium'));
    }

}