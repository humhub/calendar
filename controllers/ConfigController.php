<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers;

use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\calendar\models\MenuSettings;
use humhub\modules\calendar\models\SnippetModuleSettings;
use Yii;
use yii\helpers\Url;

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
            'model' => $model,
        ]);
    }

    public function actionMenu()
    {
        $model = new MenuSettings();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        return $this->render('menu', [
            'model' => $model,
        ]);
    }

    public function actionExport()
    {
        return $this->renderAjax('export', [
            'ical_url' => Url::to(['/external_calendar/export/ical'], true),
            'caldav_winlin_url' => Url::to(['/external_calendar/export/ical'], true),
            'caldav_macos_url' => Url::to(['/external_calendar/export/ical'], true),
        ]);
    }

}
