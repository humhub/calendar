<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers;

use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\SnippetModuleSettings;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
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

    public function actionSnippet()
    {
        $model = new SnippetModuleSettings();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        return $this->render('snippet', [
            'model' => $model
        ]);
    }

}