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
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\models\CalendarEntry;
use Yii;


/**
 * Class DownloadIcsLink
 * @package humhub\modules\calendar\widgets
 */
class DownloadIcsLink extends Widget
{

    /**
     * @var CalendarEventIF
     */
    public $calendarEntry = null;

    public function run()
    {
        if ($this->calendarEntry === null) {
            return;
        }

        return Html::a(Yii::t('CalendarModule.base', 'Download as ICS file'), Url::toEntryDownloadICS($this->calendarEntry), ['target' => '_blank']);
    }
}