<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\tests\codeception\fixtures;

use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\calendar\models\reminder\CalendarReminderSent;
use yii\test\ActiveFixture;

class CalendarReminderSentFixture extends ActiveFixture
{
    public $modelClass = CalendarReminderSent::class;
    public $dataFile = '@calendar/tests/codeception/fixtures/data/calendarReminderSent.php';
}
