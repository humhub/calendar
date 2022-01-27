<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;

/**
 * ParticipantAdd widget to add new participants to the Calendar entry without invitation
 */
class ParticipantAdd extends Widget
{
    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('participantAdd', [
            'statuses' => ParticipantItem::getStatuses(),
        ]);
    }
}
