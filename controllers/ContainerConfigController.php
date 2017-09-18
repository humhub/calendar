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


use Yii;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\admin\permissions\ManageSpaces;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\calendar\permissions\ManageEntry;
use humhub\modules\calendar\models\DefaultSettings;
use humhub\modules\content\components\ContentContainerController;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\web\HttpException;

class ContainerConfigController extends ContentContainerController
{
    /**
     * @var CalendarService
     */
    protected $calendarService;

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
        return [
          ['permission' => [ManageSpaces::class, ManageEntry::class]]
        ];
    }

    public function actionIndex()
    {
        $model = new DefaultSettings(['contentContainer' => $this->contentContainer]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        return $this->render('@calendar/views/common/defaultConfig', [
            'model' => $model
        ]);
    }

    public function actionResetConfig()
    {
        $model = new DefaultSettings(['contentContainer' => $this->contentContainer]);
        $model->reset();
        $this->view->saved();
        return $this->render('@calendar/views/common/defaultConfig', [
            'model' => $model
        ]);
    }

    public function actionTypes()
    {
        $typeDataProvider = new ActiveDataProvider([
            // TODO: replace with findByContainer with includeGlobal
            'query' => CalendarEntryType::find()->andWhere(['or',
                ['content_tag.contentcontainer_id' => $this->contentContainer->contentcontainer_id],
                'content_tag.contentcontainer_id IS NULL',
            ])
        ]);

        return $this->render('@calendar/views/common/typesConfig', [
            'typeDataProvider' => $typeDataProvider,
            'createUrl' => $this->contentContainer->createUrl('/calendar/container-config/edit-type'),
            'contentContainer' => $this->contentContainer
        ]);
    }

    public function actionEditType($id = null)
    {
        if($id) {
            $entryType = CalendarEntryType::find()->where(['id' => $id])->andWhere(['contentcontainer_id' => $this->contentContainer->contentcontainer_id])->one();
        } else {
            $entryType = new CalendarEntryType($this->contentContainer);
        }

        if(!$entryType) {
            throw new HttpException(404);
        }

        if($entryType->load(Yii::$app->request->post()) && $entryType->save()) {
            $this->view->saved();
            return $this->htmlRedirect($this->contentContainer->createUrl('/calendar/container-config/types'));
        }

        return $this->renderAjax('/common/editTypeModal', ['model' => $entryType]);
    }

    public function actionDeleteType($id)
    {
        $this->forcePostRequest();

        $entryType = CalendarEntryType::find()->where(['id' => $id])->andWhere(['contentcontainer_id' => $this->contentContainer->contentcontainer_id])->one();

        if(!$entryType) {
            throw new HttpException(404);
        }

        $entryType->delete();

        return $this->htmlRedirect($this->contentContainer->createUrl('/calendar/container-config/types'));
    }

    public function actionCalendars()
    {
        return $this->render('@calendar/views/common/calendarsConfig', [
            'contentContainer' => $this->contentContainer,
            'calendars' => $this->calendarService->getCalendarItemTypes($this->contentContainer)
        ]);
    }

    public function actionEditCalendars($key)
    {
        $item = $this->calendarService->getItemType($key, $this->contentContainer);

        if(!$item) {
            throw new HttpException(404);
        }

        if($item->load(Yii::$app->request->post()) && $item->save()) {
            $this->view->saved();
            return $this->htmlRedirect($this->contentContainer->createUrl('/calendar/container-config/calendars'));
        }

        return $this->renderAjax('@calendar/views/common/editTypeModal', ['model' => $item]);
    }
}