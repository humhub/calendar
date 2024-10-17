<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\activities;

use humhub\modules\activity\components\BaseActivity;
use humhub\modules\activity\interfaces\ConfigurableActivityInterface;
use Yii;

/**
 * ResponseAttend Activity
 *
 * @author luke
 */
class ResponseAttend extends BaseActivity implements ConfigurableActivityInterface
{
    public $viewName = 'response_attend';
    public $moduleId = 'calendar';

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Yii::t('CalendarModule.notification', 'Calendar: attend');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Yii::t('CalendarModule.notification', 'Whenever someone participates in an event.');
    }
}
