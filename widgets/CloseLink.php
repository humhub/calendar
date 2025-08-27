<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 21.07.2017
 * Time: 17:28
 */

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\helpers\Url;
use Yii;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\widgets\WallEntryControlLink;

class CloseLink extends WallEntryControlLink
{
    /**
     * @var CalendarEntry
     */
    public $entry;

    public function init()
    {
        if ($this->entry->closed) {
            $this->label = Yii::t('CalendarModule.base', 'Reopen Event');
            $this->icon = 'fa-check';
        } else {
            $this->label = Yii::t('CalendarModule.base', 'Cancel Event');
            $this->icon = 'fa-times';
        }

        $this->options = [
            'data-action-click' => 'toggleClose',
            'data-action-target' => "[data-calendar-entry='" . $this->entry->id . "']",
            'data-action-url' => Url::toEntryToggleClose($this->entry),
        ];

        parent::init();
    }

}
