<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 23.07.2017
 * Time: 23:00
 */

namespace humhub\modules\calendar\controllers;

use humhub\modules\content\components\ContentContainerControllerAccess;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;

class ContainerConfigController extends AbstractConfigController
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [[ContentContainerControllerAccess::RULE_USER_GROUP_ONLY => [Space::USERGROUP_ADMIN, User::USERGROUP_SELF]]];
    }
}
