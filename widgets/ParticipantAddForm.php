<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntry;

/**
 * ParticipantAddForm to display a form to add participants to the Calendar entry (without invitation)
 */
class ParticipantAddForm extends Widget
{
    /**
     * @var CalendarEntry
     */
    public $entry;

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (!$this->entry->content->canEdit()) {
            return '';
        }

        return $this->render('participantAddForm', [
            'entry' => $this->entry,
        ]);
    }
}
