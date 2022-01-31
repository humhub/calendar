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
 * ParticipantInviteForm to display a form to invite participants to the Calendar entry
 */
class ParticipantInviteForm extends Widget
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
        if (!$this->entry->canInvite()) {
            return '';
        }

        return $this->render('participantInviteForm', [
            'entry' => $this->entry,
        ]);
    }
}
