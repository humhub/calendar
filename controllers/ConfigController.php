<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\calendar\models\DefaultSettings;
use humhub\modules\admin\components\Controller;
use humhub\modules\calendar\models\SnippetModuleSettings;
use yii\web\HttpException;

/**
 * 
 */
class ConfigController extends Controller
{
    /**
     * @var CalendarService
     */
    public $calendarService;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->calendarService = $this->module->get(CalendarService::class);
    }

    /**
     * @inheritdoc
     */
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

    public function actionTypes()
    {
        $typeDataProvider = new ActiveDataProvider([
            //TODO: replace with findGlobal() after v1.2.3
            'query' => CalendarEntryType::find()->andWhere('content_tag.contentcontainer_id IS NULL')
        ]);

        return $this->render('@calendar/views/common/typesConfig', [
            'typeDataProvider' => $typeDataProvider,
            'createUrl' => URL::to(['/calendar/config/edit-type']),
            'contentContainer' => null
        ]);
    }

    public function actionEditType($id = null)
    {
        if($id) {
            $entryType = CalendarEntryType::find()->where(['id' => $id])->andWhere('contentcontainer_id IS NULL')->one();
        } else {
            $entryType = new CalendarEntryType();
        }

        if(!$entryType) {
            throw new HttpException(404);
        }

        if($entryType->load(Yii::$app->request->post()) && $entryType->save()) {
            $this->view->saved();
            return $this->htmlRedirect(URL::to(['/calendar/config/types']));
        }

        return $this->renderAjax('@calendar/views/common/editTypeModal', ['model' => $entryType]);
    }

    public function actionCalendars()
    {
        return $this->render('@calendar/views/common/calendarsConfig', [
            'contentContainer' => null,
            'calendars' => $this->calendarService->getCalendarItemTypes()
        ]);
    }

    public function actionEditCalendars($key)
    {
        $item = $this->calendarService->getItemType($key);

        if(!$item) {
            throw new HttpException(404);
        }

        if($item->load(Yii::$app->request->post()) && $item->save()) {
            $this->view->saved();
            return $this->htmlRedirect(URL::to(['/calendar/config/calendars']));
        }

        return $this->renderAjax('@calendar/views/common/editTypeModal', ['model' => $item]);
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
