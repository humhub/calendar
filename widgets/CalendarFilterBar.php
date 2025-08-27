<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\content\helpers\ContentContainerHelper;
use humhub\modules\content\models\ContentTag;
use Yii;

/**
 * Class CalendarFilterBar
 * @package humhub\modules\calendar\widgets
 */
class CalendarFilterBar extends Widget
{
    public $filters = [];
    public $selectors = [];

    public $showControls = true;
    public $showFilter = true;
    public $showSelectors = true;
    public $showTypes = true;

    public $canConfigure = false;

    public function run()
    {
        if (Yii::$app->user->isGuest) {
            return '';
        }

        $currentContentContainer = ContentContainerHelper::getCurrent();
        $typesQuery = ContentTag::find()->where(['type' => CalendarEntryType::class]);
        if ($currentContentContainer) {
            $typesQuery->andWhere(['contentcontainer_id' => $currentContentContainer->contentcontainer_id]);
        }
        $this->showTypes = $this->showTypes && $typesQuery->exists();

        return $this->render('calendarFilterBar', [
            'filters' => $this->filters,
            'canConfigure' => $this->canConfigure,
            'selectors' => $this->selectors,
            'showFilters' => $this->showFilter,
            'showSelectors' => $this->showSelectors,
            'showTypes' => $this->showTypes,
            'showControls' => $this->showControls,
        ]);
    }
}
