<?php

namespace humhub\modules\calendar\controllers;

use DateTime;
use humhub\widgets\ModalDialog;
use Yii;
use yii\helpers\Json;
use humhub\components\Controller;
use humhub\modules\calendar\models\CalendarEntry;
use yii\helpers\Url;
use yii\web\HttpException;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\calendar\models\SnippetModuleSettings;


/**
 * GlobalController provides a global view.
 *
 * @package humhub.modules_core.calendar.controllers
 * @author luke
 */
class GlobalController extends Controller
{

    public $hideSidebar = true;

    public function beforeAction($action)
    {
        if (!SnippetModuleSettings::instance()->showGlobalCalendarItems()) {
            throw new HttpException('500', 'Calendar module is not enabled for your user!');
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        if(!Yii::$app->user->isGuest){
            $configureUrl = Yii::$app->user->getIdentity()->createUrl('/calendar/container-config');
            $moduleEnabled = Yii::$app->user->getIdentity()->isModuleEnabled('calendar');
        } else {
            $moduleEnabled = false;
            $configureUrl = null;
        }



        return $this->render('index', [
                    'selectors' => $this->getSelectorSettings(),
                    'filters' => $this->getFilterSettings(),
                    'canConfigure' => $moduleEnabled,
                    'configureUrl' => $configureUrl,
                    'editUrl' => Url::to(['/calendar/entry/edit'])
        ]);
    }

    /**
     * @return array|mixed calendar selector settings
     */
    private function getSelectorSettings()
    {
        if(Yii::$app->user->isGuest) {
            return [];
        }

        $lastSelectorsJson = Yii::$app->user->getIdentity()->getSetting('lastSelectors', 'calendar');
        if ($lastSelectorsJson != "") {
            $selectors = Json::decode($lastSelectorsJson);
        }

        if(empty($lastSelectorsJson)) {
            $selectors = [
                ActiveQueryContent::USER_RELATED_SCOPE_OWN_PROFILE,
                ActiveQueryContent::USER_RELATED_SCOPE_SPACES,
            ];
        }

        return $selectors;
    }

    /**
     * @return array|mixed calendar filter settings
     */
    private function getFilterSettings()
    {
        if(Yii::$app->user->isGuest) {
            return [];
        }

        $lastFilterJson = Yii::$app->user->getIdentity()->getSetting('lastFilters', 'calendar');
        if ($lastFilterJson != "") {
            $filters = Json::decode($lastFilterJson);
        }

        if(empty($filters)) {
            $filters = [];
        }

        return $filters;
    }

    public function actionLoadAjax($start, $end)
    {
        $output = [];

        if(!Yii::$app->user->isGuest) {
            $selectors = Yii::$app->request->get('selectors', []);
            $filters = Yii::$app->request->get('filters', []);

            Yii::$app->user->getIdentity()->setSetting('lastSelectors', Json::encode($selectors), 'calendar');
            Yii::$app->user->getIdentity()->setSetting('lastFilters', Json::encode($filters), 'calendar');

            $entries = CalendarEntry::getEntriesByRange(new DateTime($start), new DateTime($end), $selectors, $filters);
        } else {
            $entries = CalendarEntry::getEntriesByRange(new DateTime($start), new DateTime($end));
        }

        foreach ($entries as $entry) {
            $output[] = $entry->getFullCalendarArray();
        }

        return $this->asJson($output);
    }

    public function actionEnable()
    {
        $user = Yii::$app->user->getIdentity();

        $cancelButton = '<button data-modal-close class="btn btn-default">'.Yii::t('base' ,'Cancel').'</button>';
        $enableButton = '<button data-action-click="content.container.enableModule" data-action-url="'.$user->createUrl('/user/account/enable-module', ['moduleId' => 'calendar']).'" data-ui-loader class="btn btn-primary">'. Yii::t('CalendarModule.base' ,'Enable').'</button>';
        $nextButton = '<button data-action-click="calendar.enabled" class="btn btn-primary disable" style="display:none" data-ui-loader>'.Yii::t('CalendarModule.base' ,'Next').'</button>';

        return ModalDialog::widget([
            'header' => Yii::t('CalendarModule.base', '<strong>Add</strong> profile calendar'),
            'body' => Yii::t('CalendarModule.base', 'In order to add events to your profile, you have to enable the calendar module first.'),
            'footer' => $enableButton.$nextButton.$cancelButton,
            'centerText' => true
        ]);
    }
}
