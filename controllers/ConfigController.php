<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers;


use humhub\modules\admin\permissions\ManageModules;
use Yii;

class ConfigController extends AbstractConfigController
{
    public $requireContainer = false;

    public $subLayout = "@humhub/modules/admin/views/layouts/main";

    /**
     * @inheritdoc
     */
    public function getAccessRules()
    {
        return [['permissions' => ManageModules::class]];
    }

    public function getAccess()
    {
        return Yii::createObject($this->access);
    }

}