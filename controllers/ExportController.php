<?php

namespace humhub\modules\calendar\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\AuthTokenService;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\content\models\Content;
use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Inflector;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class ExportController extends Controller
{
    private const EXPORT_MIME = 'text/calendar';
    private ?CalendarService $calendarService;

    public function init()
    {
        parent::init();

        $this->calendarService =  Yii::$app->getModule('calendar')->get(CalendarService::class);
    }

    public $access = ControllerAccess::class;

    public function getAccessRules()
    {
        return [
            ['login' => ['modal', 'event']],
        ];
    }

    public function actionModal($guid, $global)
    {
        return $this->renderAjax('modal', [
            'token' => AuthTokenService::instance()->iCalEncrypt(Yii::$app->user->id, $guid, $global),
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

        $ics = $content->model->generateIcs(true);
        $uid = $content->model->getUid() ?: $this->uniqueId;

        if (empty($ics)) {
            throw new BadRequestHttpException('Selected content does not implement calendar interface');
        }

        return Yii::$app->response->sendContentAsFile(
            $ics,
            $uid . '.ics',
            ['mimeType' => static::EXPORT_MIME],
        );
    }

    public function actionCalendar($token)
    {
        $data = AuthTokenService::instance()->iCalDecrypt($token);

        if (!$data) {
            throw new NotFoundHttpException();
        }

        [$userId, $guid, $global] = $data;

        $user = User::find()->active()->andWhere(['id' => $userId])->one();

        if (!$user) {
            throw new NotFoundHttpException();
        }

        $contentContainer = ContentContainer::findOne(['guid' => $guid]);

        // Login only for this request
        Yii::$app->user->enableSession = false;
        Yii::$app->user->login($user);

        /** @var CalendarEntry[] $events */
        $events = $this->calendarService->getCalendarItems(
            null,
            null,
            [],
            $global ? null : $contentContainer->polymorphicRelation,
        );

        /**
         * Google Calendar strips details of private/confidential events,
         * To make events visible in Google Calendar, force visibility to PUBLIC
         * when the request comes from the Google Calendar.
         */
        if (Yii::$app->request->userAgent == 'Google-Calendar-Importer') {
            foreach ($events as $event) {
                if (!empty($event->content)) {
                    $event->content->visibility = Content::VISIBILITY_PUBLIC;
                }
            }
        }

        $ics = CalendarUtils::generateIcal($events, $contentContainer->polymorphicRelation->displayName);

        return Yii::$app->response->sendContentAsFile(
            $ics,
            Inflector::slug($contentContainer->polymorphicRelation->displayName, '_') . '.ics',
            ['mimeType' => static::EXPORT_MIME],
        );
    }
}
