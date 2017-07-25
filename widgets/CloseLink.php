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
        if($this->entry->closed) {
            $this->label = Yii::t('ContentModule.widgets_views_editLink', 'Reopen Event');
            $this->icon = 'fa-check';
        } else {
            $this->label = Yii::t('ContentModule.widgets_views_editLink', 'Cancel Event');
            $this->icon = 'fa-times';
        }

        $this->options = [
            'data-action-click' => 'toggleClose',
            'data-action-target' =>"[data-calendar-entry='".$this->entry->id."']",
            'data-action-url' => $this->entry->content->container->createUrl('/calendar/entry/toggle-close', ['id' => $this->entry->id])
        ];

        parent::init();
    }

}