<?php

namespace humhub\modules\calendar\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\models\Content;
use Yii;
use yii\base\Exception;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

class IcalController extends Controller
{
    /**
     * @var CalendarService
     */
    public $calendarService;

    public const EXPORT_MIME = 'text/calendar';

    /**
     * @return array
     */
    public function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
        ];
    }

    public function init()
    {
        parent::init();
        $this->calendarService =  Yii::$app->getModule('calendar')->get(CalendarService::class);
    }

    /**
     * @param $id
     * @return \yii\console\Response|\yii\web\Response
     * @throws Exception
     * @throws HttpException
     * @throws \Throwable
     * @throws \yii\web\RangeNotSatisfiableHttpException
     */
    public function actionExport($id)
    {
        $model = $this->getModel($id);

        $ics = $model->generateIcs();

        if (empty($ics)) {
            throw new HttpException(400, 'Selected content does not implement calendar interface');
        }

        $uid = $model->getUid() ?: $this->uniqueId;

        return Yii::$app->response->sendContentAsFile($ics, $uid . '.ics', ['mimeType' => static::EXPORT_MIME]);
    }

    /**
     * @param $id
     * @return CalendarEntry
     * @throws HttpException
     * @throws \Throwable
     * @throws Exception
     */
    public function getModel($id)
    {
        $content = Content::findOne(['id' => $id]);

        if (!$content) {
            throw new NotFoundHttpException();
        }

        if (!$content->canView()) {
            throw new HttpException(403);
        }

        $model = $content->getModel();

        if (!$model) {
            throw new NotFoundHttpException();
        }

        return $model;
    }
}
