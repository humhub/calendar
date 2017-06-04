<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers;

use Yii;
use humhub\modules\admin\components\Controller;
use humhub\modules\calendar\models\ModuleSettings;

/**
 * 
 */
class ConfigController extends Controller
{

    /**
     * Configuration action for system admins.
     */
    public function actionIndex()
    {
        $model = new ModuleSettings();
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        return $this->render('index', [
            'model' => $model
        ]);
    }
}
