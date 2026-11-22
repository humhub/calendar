<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\tests\codeception\unit\caldav;

use calendar\CalendarUnitTest;
use humhub\modules\calendar\Events;
use Yii;

/**
 * The errors of CalDAV clients are answered by the CalDAV controller, those of every other
 * request by the core: its error action disables the session, which a page must keep.
 */
class ErrorActionTest extends CalendarUnitTest
{
    public function testARequestKeepsTheErrorActionOfTheCore()
    {
        $errorAction = Yii::$app->errorHandler->errorAction;

        try {
            Events::onBeforeRequest();
            $this->assertSame($errorAction, Yii::$app->errorHandler->errorAction);
        } finally {
            Yii::$app->errorHandler->errorAction = $errorAction;
        }
    }
}
