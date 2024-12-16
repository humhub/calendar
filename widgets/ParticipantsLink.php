<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\widgets\WallEntryControlLink;
use Yii;

class ParticipantsLink extends WallEntryControlLink
{
    /**
     * @var CalendarEntry
     */
    public $entry;

    public function init()
    {
        $this->label = Yii::t('CalendarModule.base', 'Participants');
        $this->icon = 'fa-users';
        $this->options = [
            'data-action-click' => 'editModal',
            'data-action-url' => $this->entry->content->canEdit()
                ? Url::toEditEntryParticipation($this->entry)
                : Url::toParticipationUserList($this->entry),
        ];

        parent::init();
    }

}
