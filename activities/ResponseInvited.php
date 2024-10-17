<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\activities;

use humhub\modules\activity\components\BaseActivity;
use humhub\modules\activity\interfaces\ConfigurableActivityInterface;
use Yii;

/**
 * ResponseInvited Activity
 *
 * @author luke
 */
class ResponseInvited extends BaseActivity implements ConfigurableActivityInterface
{
    public $viewName = 'response_invited';
    public $moduleId = 'calendar';

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Yii::t('CalendarModule.notification', 'Calendar: Invite');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Yii::t('CalendarModule.notification', 'Whenever someone invites to participate in an event.');
    }
}
