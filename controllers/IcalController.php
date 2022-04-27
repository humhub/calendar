<?php


namespace humhub\modules\calendar\controllers;

use humhub\components\access\ControllerAccess;
use Yii;
use yii\base\Exception;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use humhub\components\Controller;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use humhub\modules\calendar\interfaces\VCalendar;
use humhub\modules\content\models\Content;


class IcalController extends Controller
{
    /**
     * @var CalendarService
     */
    public $calendarService;

    const EXPORT_MIME = 'text/calendar';

    /**
     * @return array
     */
    public function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY]
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
        /* @var $events RecurrentEventIF[] */
        $events = [$this->getEvent($id)];

        if (RecurrenceHelper::isRecurrent($events[0]) && !RecurrenceHelper::isRecurrentRoot($events[0])) {
            array_unshift($events, $events[0]->getRecurrenceQuery()->getRecurrenceRoot());
            $events[1]->setUid($events[1]->getUid() . '-' . $events[1]->id);
        }

        $uid = $events[0]->getUid() ?: $this->uniqueId;

        $calendar = VCalendar::withEvents($events, CalendarUtils::getSystemTimeZone(true));

        return Yii::$app->response->sendContentAsFile($calendar->serialize(), $uid.'.ics', ['mimeType' => static::EXPORT_MIME]);
    }

    public function actionGenerateics()
    {
        $calendarEntry = $this->getCalendarEntry(Yii::$app->request->get('id'));
        $ics = $calendarEntry->generateIcs();
        return Yii::$app->response->sendContentAsFile($ics, uniqid() . '.ics', ['mimeType' => static::EXPORT_MIME]);
    }

    /**
     * @param $id
     * @return CalendarEventIF
     * @throws HttpException
     * @throws \Throwable
     * @throws Exception
     */
    public function getEvent($id)
    {
        $content = Content::findOne(['id' => $id]);

        if(!$content) {
            throw new NotFoundHttpException();
        }

        if(!$content->canView()) {
            throw new HttpException(403);
        }

        $model = $content->getModel();

        if(!$model) {
            throw new NotFoundHttpException();
        }

        $event = CalendarUtils::getCalendarEvent($model);

        if(!$event) {
            throw new HttpException(400, 'Selected content does not implement calendar interface');
        }

        return $event;
    }
}