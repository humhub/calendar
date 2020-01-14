<?php


namespace humhub\modules\calendar\tests\codeception\unit\entry;


use calendar\CalendarUnitTest;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\space\models\Space;
use Yii;

class LocaleTest extends CalendarUnitTest
{
    private $space;

    public function _before()
    {
        parent::_before();
        $this->becomeUser('User2');
        Yii::$app->user->identity->updateAttributes(['time_zone' => 'Europe/Berlin']);
        Yii::$app->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->timeZone = 'Europe/Berlin';
        Yii::$app->formatter->locale = 'de';
        $this->space =  Space::findOne(['id' => 1]);
    }

    public function testAllDayDefaultTimeWithMeridiam()
    {
        Yii::$app->user->identity->language = 'el';
        Yii::$app->i18n->autosetLocale();

        $form = CalendarEntryForm::createEntry($this->space);
        $form->setDefaultTime();
        $this->assertEquals('10:00 AM', $form->start_time);
        $this->assertEquals('12:00 PM', $form->end_time);
    }

    public function testAllDayDefaultTimeWithoutMeridiam()
    {
        Yii::$app->user->identity->language = 'de';
        Yii::$app->i18n->autosetLocale();

        $form = CalendarEntryForm::createEntry($this->space);
        $form->setDefaultTime();
        $this->assertEquals('10:00', $form->start_time);
        $this->assertEquals('12:00', $form->end_time);
    }

    public function testAllLocalesAllDay()
    {
        $start = new \DateTime('2019-11-13 00:00:00');
        $end = new \DateTime('2019-11-13 00:00:00');

        foreach(Yii::$app->params['availableLanguages'] as $locale => $name) {
            $this->assertLocale($locale, $start, $end, 1);
        }
    }

    public function testAllLocalesNonAllDay()
    {
        $start = new \DateTime('2019-11-13 13:00:00');
        $end = new \DateTime('2019-11-14 15:00:00');

        foreach(Yii::$app->params['availableLanguages'] as $locale => $name) {
            $this->assertLocale($locale, $start, $end);
        }
    }

    public function assertLocale($locale, $start, $end, $allDay = 0)
    {
        Yii::$app->user->identity->language = $locale;
        Yii::$app->i18n->autosetLocale();
        $startDate = Yii::$app->formatter->asDate($start, 'short');
        $startTime = $start->format(CalendarUtils::getTimeFormat(true));

        $endDate = Yii::$app->formatter->asDate($end, 'short');
        $endTime = $end->format(CalendarUtils::getTimeFormat(true));

        $form = CalendarEntryForm::createEntry($this->space, '2019-11-13 13:00:00', '2019-11-13 15:00:00');
        $this->assertTrue($form->load([
            'CalendarEntry' => [
                'title' => 'Test title',
                'all_day' => $allDay,
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'start_date' => $startDate,
                'start_time' => $startTime,
                'end_date' => $endDate,
                'end_time' => $endTime
            ]
        ]), "Error loading form with locale $locale");
        $this->assertTrue($form->save(), "Error saving form with locale $locale");
        $entry = CalendarEntry::findOne(['id' => $form->entry->id]);
        $this->assertEquals($start, $entry->getStartDateTime(), "Invalid start time with locale $locale");

        $expectedEnd = ($allDay) ? $end->modify('+1 day')->setTime(0,0,0) : $end;

        $this->assertEquals($expectedEnd, $entry->getEndDateTime(), "Invalid end time with locale $locale");
    }

    public function testSubmitFormForNewEventDateFormat()
    {
        Yii::$app->formatter->locale = 'en-US';

        $space1 = Space::findOne(['id' => 1]);
        $calendarForm = CalendarEntryForm::createEntry($space1, '2019-11-13 13:00:00', '2019-11-13 15:00:00');

        $this->assertTrue($calendarForm->load([
            'CalendarEntry' => [
                'title' => 'Test title',
                'all_day' => '0',
                'description' => 'TestDescription',
                'participation_mode' => 2
            ],
            'CalendarEntryForm' => [
                'is_public' => '1',
                'start_date' => '11/11/19',
                'start_time' => '12:00 AM',
                'end_date' => '11/11/19',
                'end_time' => '3:00 PM'
            ]
        ]));

        $this->assertTrue($calendarForm->save());
        $this->assertEquals('2019-11-11', $calendarForm->start_date);
        $this->assertEquals('12:00 AM', $calendarForm->start_time);
        $this->assertEquals('2019-11-11', $calendarForm->end_date);
        $this->assertEquals('03:00 PM', $calendarForm->end_time);
        $this->assertEquals('2019-11-11 00:00:00', $calendarForm->entry->start_datetime);
        $this->assertEquals('2019-11-11 15:00:00', $calendarForm->entry->end_datetime);
    }
}