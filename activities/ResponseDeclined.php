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
 * ResponseDeclined Activity
 *
 * @author luke
 */
class ResponseDeclined extends BaseActivity implements ConfigurableActivityInterface
{
    public $viewName = 'response_declined';
    public $moduleId = 'calendar';

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Yii::t('CalendarModule.notification', 'Calendar: decline');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Yii::t('CalendarModule.notification', 'Whenever someone declines to participate in an event.');
    }
}
