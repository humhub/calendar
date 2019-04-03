<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;


use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\helpers\ContentContainerHelper;
use humhub\widgets\BaseStack;

class CalendarControls extends BaseStack
{
    public $seperator = "&nbsp;";

    /**
     * @var ContentActiveRecord
     */
    public $container;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->container = ContentContainerHelper::getCurrent();
        parent::init();
    }

}