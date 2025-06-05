<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\tests\codeception\fixtures;

use humhub\modules\content\models\Content;
use yii\test\ActiveFixture;

class CalendarEntryContentFixture extends ActiveFixture
{
    public $modelClass = Content::class;
    public $dataFile = '@calendar/tests/codeception/fixtures/data/calendarEntryContent.php';
    public $depends = [];
}
