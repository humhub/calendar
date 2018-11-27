<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\permissions;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

/**
 * CreateEntry Permission
 */
class ManageEntry extends \humhub\libs\BasePermission
{
    /**
     * @inheritdoc
     */
    protected $moduleId = 'calendar';

    /**
     * @inheritdoc
     */
    public $defaultAllowedGroups = [
        Space::USERGROUP_OWNER,
        Space::USERGROUP_ADMIN,
        Space::USERGROUP_MODERATOR,
        User::USERGROUP_SELF
    ];

    /**
     * @inheritdoc
     */
    protected $fixedGroups = [
        Space::USERGROUP_USER,
        User::USERGROUP_FRIEND,
        User::USERGROUP_GUEST,
        User::USERGROUP_USER,
        User::USERGROUP_FRIEND,
        Space::USERGROUP_GUEST,
    ];



    public function getTitle()
    {
        return Yii::t('CalendarModule.permissions', 'Manage entries');
    }

    public function getDescription()
    {
        return Yii::t('CalendarModule.permissions', 'Allows the user to edit/delete existing calendar entries');
    }


}
