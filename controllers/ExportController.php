<?php

namespace humhub\modules\calendar\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\ical\IcalTokenService;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\content\models\Content;
use humhub\modules\user\models\User;
use Yii;
use yii\base\Event;
use yii\helpers\Inflector;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class ExportController extends Controller
{
    private const EXPORT_MIME = 'text/calendar';
    private ?CalendarService $calendarService;

    public function beforeAction($action)
    {
        Yii::$app->on('beforeTwoFaCheck', function (Event $event) use ($action) {
            $event->handled = $action->id === 'export';
        });

        return parent::beforeAction($action);
    }

    public function init()
    {
        parent::init();

        $this->calendarService =  Yii::$app->getModule('calendar')->get(CalendarService::class);
    }

    public function getAccessRules()
    {
        return [
            ['login' => ['modal', 'event']],
        ];
    }

    public function actionModal($guid)
    {
        return $this->renderAjax('modal', [
            'token' => IcalTokenService::instance()->encrypt($guid)
        ]);
    }

    public function actionEvent($id)
    {
        $content = Content::findOne(['id' => $id]);

        if (!$content || !$content->getModel()) {
            throw new NotFoundHttpException();
        }

        if (!$content->canView()) {
            throw new ForbiddenHttpException(403);
        }

        $ics = $content->model->generateIcs();
        $uid = $content->model->getUid() ?: $this->uniqueId;

        if (empty($ics)) {
            throw new BadRequestHttpException('Selected content does not implement calendar interface');
        }

        return Yii::$app->response->sendContentAsFile(
            $ics,
            $uid . '.ics',
            ['mimeType' => static::EXPORT_MIME]
        );
    }

    public function actionCalendar($token)
    {
        $contentContainer = ContentContainer::findOne(['guid' => IcalTokenService::instance()->decrypt($token)]);

        if (!$contentContainer) {
            throw new NotFoundHttpException();
        }

        // Login as owner of the content container only for this request
        Yii::$app->user->enableSession = false;
        Yii::$app->user->login(User::findOne(['id' => $contentContainer->owner_user_id]));

        $events = $this->calendarService->getCalendarItems(null, null, [], $contentContainer->polymorphicRelation);
        $ics = CalendarUtils::generateIcal($events);

        return Yii::$app->response->sendContentAsFile(
            $ics,
            Inflector::slug($contentContainer->polymorphicRelation->displayName, '_') . '.ics',
            ['mimeType' => static::EXPORT_MIME]
        );
    }
}
