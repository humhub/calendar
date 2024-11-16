<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\content\components\ContentActiveRecord;

/**
 * Class DownloadIcsLink
 * @package humhub\modules\calendar\widgets
 */
class ReminderLink extends Widget
{
    /**
     * @var ContentActiveRecord
     */
    public $entry = null;

    public function run()
    {
        if (!$this->entry || !$this->entry instanceof ContentActiveRecord || !$this->entry instanceof CalendarEventIF) {
            return;
        }

        return $this->render('reminderLink', [
            'entry' => $this->entry,
        ]);
    }
}
