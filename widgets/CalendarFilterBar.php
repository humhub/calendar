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

class CalendarFilterBar extends Widget
{
    public $filters = [];
    public $selectors = [];

    public $showFilter = true;
    public $showSelectors = true;

    public $canConfigure = false;
    public $configUrl;

    public function run()
    {
        if(Yii::$app->user->isGuest) {
            return;
        }

        return $this->render('calendarFilterBar', [
            'filters' => $this->filters,
            'canConfigure' => $this->canConfigure,
            'configUrl' => $this->configUrl,
            'selectors' => $this->selectors,
            'showFilters' => $this->showFilter,
            'showSelectors' => $this->showSelectors
        ]);
    }
}