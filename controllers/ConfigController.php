<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers;

use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\calendar\models\DefaultSettings;
use Yii;
use humhub\modules\admin\components\Controller;
use humhub\modules\calendar\models\SnippetModuleSettings;

/**
 *
 */
class ConfigController extends Controller
{
    public function getAccessRules()
    {
        return [['permissions' => ManageModules::class]];
    }

    /**
     * Configuration action for system admins.
     */
    public function actionIndex()
    {
        $model = new DefaultSettings();
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        return $this->render('@calendar/views/common/defaultConfig', [
            'model' => $model
        ]);
    }

    public function actionResetConfig()
    {
        $model = new DefaultSettings();
        $model->reset();
        $this->view->saved();
        return $this->render('@calendar/views/common/defaultConfig', [
            'model' => $model
        ]);
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
