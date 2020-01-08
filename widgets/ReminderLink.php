<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\libs\Html;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\interfaces\CalendarEventIF;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use Yii;


/**
 * Class DownloadIcsLink
 * @package humhub\modules\calendar\widgets
 */
class ReminderLink extends Widget
{

    /**
     * @var CalendarEventIF
     */
    public $entry = null;

    public function run()
    {
        if ($this->entry === null) {
            return;
        }

        return ModalButton::asLink(Yii::t('CalendarModule.base', 'Set reminder'))->load(Url::toUserLevelReminderConfig($this->entry))->loader(true);
    }
}