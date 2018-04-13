<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\libs\Html;
use humhub\modules\calendar\models\CalendarEntry;
use Yii;


/**
 * Class DownloadIcsLink
 * @package humhub\modules\calendar\widgets
 */
class DownloadIcsLink extends Widget
{

    /**
     * @var CalendarEntry
     */
    public $calendarEntry = null;

    public function run()
    {
        if ($this->calendarEntry === null) {
            return;
        }

        return Html::a(Yii::t('CalendarModule.base', 'Download as ICS file'), $this->calendarEntry->content->container->createUrl('/calendar/entry/generateics', ['id' => $this->calendarEntry->id]), ['target' => '_blank']);
    }
}