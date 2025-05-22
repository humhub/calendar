<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\helpers\Html;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\widgets\modal\ModalButton;
use Yii;

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
        if (!$this->entry instanceof ContentActiveRecord || !$this->entry instanceof CalendarEventIF) {
            return '';
        }

        return Html::tag(
            'span',
            ModalButton::asLink(Yii::t('CalendarModule.base', 'Set reminder'))
                ->load(Url::toUserLevelReminderConfig($this->entry))
                ->loader(true),
            ['class' => 'calendar-entry-reminder'],
        );
    }
}
