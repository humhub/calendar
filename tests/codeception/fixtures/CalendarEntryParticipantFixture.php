<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\tests\codeception\fixtures;

use yii\test\ActiveFixture;

class CalendarEntryParticipantFixture extends ActiveFixture
{
    public $modelClass = 'humhub\modules\calendar\models\CalendarEntryParticipant';
    public $dataFile = '@calendar/tests/codeception/fixtures/data/calendarEntryParticipant.php';
   
}
