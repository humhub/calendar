<?php

namespace humhub\modules\calendar\controllers;

use Yii;
use yii\helpers\Json;
use humhub\components\Controller;
use humhub\modules\calendar\models\CalendarEntry;
use yii\web\HttpException;
use humhub\modules\content\components\ActiveQueryContent;


/**
 * GlobalController provides a global view.
 *
 * @package humhub.modules_core.calendar.controllers
 * @author luke
 */
class GlobalController extends Controller
{

    public function beforeAction($action)
    {
        if (!Yii::$app->user->getIdentity()->isModuleEnabled('calendar')) {
            throw new HttpException('500', 'Calendar module is not enabled for your user!');
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        // Restore users last selectors
        $lastSelectorsJson = Yii::$app->user->getIdentity()->getSetting('lastSelectors', 'calendar');
        if ($lastSelectorsJson != "") {
            $selectors = Json::decode($lastSelectorsJson);
        } else {
            $selectors = array(
                ActiveQueryContent::USER_RELATED_SCOPE_OWN,
                ActiveQueryContent::USER_RELATED_SCOPE_SPACES,
            );
        }

        // Restore users last used filter
        $lastFilterJson = Yii::$app->user->getIdentity()->getSetting('lastFilters', 'calendar');
        if ($lastFilterJson != "") {
            $filters = Json::decode($lastFilterJson);
        } else {
            $filters = array();
        }

        return $this->render('index', array(
                    'selectors' => $selectors,
                    'filters' => $filters,
                    'user' => Yii::$app->user->getIdentity()
        ));
    }

    public function actionLoadAjax()
    {
        Yii::$app->response->format = 'json';

        $output = array();

        $startDate = new \DateTime(Yii::$app->request->get('start'));
        $endDate = new \DateTime(Yii::$app->request->get('end'));
        $selectors = explode(",", Yii::$app->request->get('selectors'));
        $filters = explode(",", Yii::$app->request->get('filters'));

        Yii::$app->user->getIdentity()->setSetting('lastSelectors', Json::encode($selectors), 'calendar');
        Yii::$app->user->getIdentity()->setSetting('lastFilters', Json::encode($filters), 'calendar');

        $entries = CalendarEntry::getEntriesByRange($startDate, $endDate, $selectors, $filters);

        foreach ($entries as $entry) {
            $output[] = $entry->getFullCalendarArray();
        }

        return $output;
    }

}
