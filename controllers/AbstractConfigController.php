<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers;

use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\calendar\models\DefaultSettings;
use humhub\modules\calendar\models\forms\BasicSettings;
use humhub\modules\calendar\models\participation\ParticipationSettings;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\models\ContentContainer;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\HttpException;

/**
 *
 */
abstract class AbstractConfigController extends ContentContainerController
{
    public const VIEW_CONFIG_DEFAULT = '@calendar/views/common/defaultConfig';
    public const VIEW_CONFIG_TYPE = '@calendar/views/common/typesConfig';
    public const VIEW_CONFIG_EDIT_TYPE_MODAL = '@calendar/views/common/editTypeModal';
    public const VIEW_CONFIG_CALENDARS = '@calendar/views/common/calendarsConfig';

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
     * Configuration action for system admins.
     */
    public function actionIndex()
    {
        $model = new DefaultSettings(['contentContainer' => $this->contentContainer]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        return $this->render(static::VIEW_CONFIG_DEFAULT, [
            'model' => $model,
        ]);
    }

    public function actionTypes()
    {
        $query = $this->contentContainer
            ? CalendarEntryType::findByContainer($this->contentContainer, true)
            : CalendarEntryType::findGlobal();

        $typeDataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $this->render(static::VIEW_CONFIG_TYPE, [
            'typeDataProvider' => $typeDataProvider,
            'createUrl' => URL::toCreateType($this->contentContainer),
            'contentContainer' => $this->contentContainer,
        ]);
    }

    public function actionDeleteType($id)
    {
        $this->forcePostRequest();

        $model = CalendarEntryType::find()->where(['id' => $id])->one();

        $this->validateEntry($model);

        if ($model->delete()) {
            $this->view->success(Yii::t('CalendarModule.base', 'Deleted'));
        }

        return $this->htmlRedirect(Url::toConfigTypes($this->contentContainer ?? $this->getContainerFromRequest()));
    }

    public function actionEditType($id = null)
    {
        if ($id) {
            $model = CalendarEntryType::find()->where(['id' => $id])->one();
            $this->validateEntry($model);
        } else {
            $model = new CalendarEntryType($this->contentContainer);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
            return $this->htmlRedirect(URL::toConfigTypes($this->contentContainer ?? $this->getContainerFromRequest()));
        }

        return $this->renderAjax(static::VIEW_CONFIG_EDIT_TYPE_MODAL, ['model' => $model]);
    }

    public function actionCalendars()
    {
        $types = $this->calendarService->getCalendarItemTypes($this->contentContainer);
        return $this->render(static::VIEW_CONFIG_CALENDARS, [
            'contentContainer' => $this->contentContainer,
            'calendars' => $types,
        ]);
    }

    public function actionEditCalendars($key)
    {
        $item = $this->calendarService->getItemType($key, $this->contentContainer);

        if (!$item) {
            throw new HttpException(404);
        }

        if ($item->load(Yii::$app->request->post()) && $item->save()) {
            $this->view->saved();
            return $this->htmlRedirect(URL::toConfigCalendars($this->contentContainer));
        }

        return $this->renderAjax(static::VIEW_CONFIG_EDIT_TYPE_MODAL, ['model' => $item]);
    }

    public function actionResetBasicConfig()
    {
        $this->forcePostRequest();
        $model = new BasicSettings(['contentContainer' => $this->contentContainer]);
        $model->reset();
        $this->view->saved();
        $this->redirect(Url::toConfig($this->contentContainer));
    }

    public function actionResetParticipationConfig()
    {
        $this->forcePostRequest();
        $model = new ParticipationSettings(['contentContainer' => $this->contentContainer]);
        $model->reset();
        $this->view->saved();
        $this->redirect(Url::toConfig($this->contentContainer));
    }

    protected function validateEntry(CalendarEntryType $type = null)
    {
        if (!$type) {
            throw new HttpException(404);
        }

        if ($type->contentcontainer_id !== $this->getContentContainerId()) {
            throw new HttpException(400);
        }
    }

    protected function getContentContainerId()
    {
        return $this->contentContainer ? $this->contentContainer->contentcontainer_id : null;
    }

    protected function getContainerFromRequest(): ?ContentContainerActiveRecord
    {
        $guid = Yii::$app->request->get('guid', Yii::$app->request->post('guid'));
        return $guid ? ContentContainer::findRecord($guid) : null;
    }
}
