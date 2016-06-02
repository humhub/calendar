<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2016 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\permissions;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;

/**
 * CreateEntry Permission
 */
class CreateEntry extends \humhub\libs\BasePermission
{

    /**
     * @inheritdoc
     */
    public $defaultAllowedGroups = [
        Space::USERGROUP_OWNER,
        Space::USERGROUP_ADMIN,
        Space::USERGROUP_MODERATOR,
        Space::USERGROUP_MEMBER,
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
    ];

    /**
     * @inheritdoc
     */
    protected $title = "Create entry";

    /**
     * @inheritdoc
     */
    protected $description = "Allows the user to create new calendar entries";

    /**
     * @inheritdoc
     */
    protected $moduleId = 'calendar';

}
