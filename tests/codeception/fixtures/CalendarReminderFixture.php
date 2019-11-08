<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\tests\codeception\fixtures;

use humhub\modules\calendar\models\reminder\CalendarReminder;
use yii\test\ActiveFixture;

class CalendarReminderFixture extends ActiveFixture
{
    public $modelClass = CalendarReminder::class;
    public $dataFile = '@calendar/tests/codeception/fixtures/data/calendarReminder.php';
    
     public $depends = [
        CalendarReminderSentFixture::class
    ];
}
