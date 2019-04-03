<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\widgets;


use humhub\components\Widget;
use Yii;

/**
 * Class CalendarFilterBar
 * @package humhub\modules\calendar\widgets
 */
class CalendarFilterBar extends Widget
{
    public $filters = [];
    public $selectors = [];

    public $showFilter = true;
    public $showSelectors = true;

    public $canConfigure = false;

    public function run()
    {
        if(Yii::$app->user->isGuest) {
            return;
        }

        return $this->render('calendarFilterBar', [
            'filters' => $this->filters,
            'canConfigure' => $this->canConfigure,
            'selectors' => $this->selectors,
            'showFilters' => $this->showFilter,
            'showSelectors' => $this->showSelectors
        ]);
    }
}