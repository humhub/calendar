<?php

namespace humhub\modules\calendar\controllers;

use DateTime;
use humhub\components\Controller;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryDummy;
use humhub\modules\calendar\models\fullcalendar\FullCalendar;
use humhub\modules\calendar\models\SnippetModuleSettings;
use humhub\modules\calendar\widgets\CalendarFilterBar;
use humhub\modules\calendar\widgets\FilterType;
use humhub\modules\content\components\ContentContainerModuleManager;
use humhub\modules\content\components\ContentContainerSettingsManager;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;
use Yii;
use yii\web\HttpException;

/**
 * GlobalController provides a global view.
 *
 * @package humhub.modules_core.calendar.controllers
 * @author luke
 */
class GlobalController extends Controller
{
    /**
     * @inheritdoc
     */
    public $hideSidebar = true;

    /**
     * @var CalendarService
     */
    public $calendarService;

    /**
     * @return array
     */
    public function getAccessRules()
    {
        return [
            ['login' => ['enable', 'select']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->appendPageTitle(Yii::t('CalendarModule.base', 'Calendar'));
        parent::init();
        $this->calendarService = $this->module->get(CalendarService::class);
    }

    public function beforeAction($action)
    {
        if (!SnippetModuleSettings::instantiate()->showGlobalCalendarItems()) {
            throw new HttpException('500', 'Calendar module is not enabled on your profile!');
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        return $this->render('index', [
            'viewMode' => $this->getViewModeSetting(),
            'calendars' => $this->getCalendarsSetting(),
            'show' => $this->getShowSetting(),
            'types' => $this->getTypesSetting(),
            'editUrl' => Url::to(['/calendar/entry/edit']),
        ]);
    }

    /**
     * @return string current CalendarFilterBar::VIEW_* setting, defaults to VIEW_MY_CALENDARS.
     *  Note: VIEW_NETWORK is only ever persisted/returned here after the user explicitly
     *  picked it - it can never be reached implicitly.
     */
    private function getViewModeSetting(): string
    {
        return Yii::$app->user->isGuest
            ? CalendarFilterBar::VIEW_MY_CALENDARS
            : $this->getUserSettings()->get('lastViewMode', CalendarFilterBar::VIEW_MY_CALENDARS);
    }

    /**
     * @return string current CalendarFilterBar::CALENDARS_* setting, defaults to CALENDARS_ALL.
     */
    private function getCalendarsSetting(): string
    {
        return Yii::$app->user->isGuest
            ? CalendarFilterBar::CALENDARS_ALL
            : $this->getUserSettings()->get('lastCalendars', CalendarFilterBar::CALENDARS_ALL);
    }

    /**
     * @return string current CalendarFilterBar::SHOW_* setting, defaults to SHOW_ALL.
     */
    private function getShowSetting(): string
    {
        return Yii::$app->user->isGuest
            ? CalendarFilterBar::SHOW_ALL
            : $this->getUserSettings()->get('lastShow', CalendarFilterBar::SHOW_ALL);
    }

    /**
     * @return int[] persisted "Event types" (CalendarEntryType ids) setting, defaults to [].
     */
    private function getTypesSetting(): array
    {
        return Yii::$app->user->isGuest
            ? []
            : (array) $this->getUserSettings()->getSerialized('lastTypes', []);
    }

    public function getUserSettings(): ?ContentContainerSettingsManager
    {
        return Yii::$app->user->isGuest
            ? null
            : Yii::$app->getModule('calendar')->settings->user();
    }

    public function actionSelect($start = null, $end = null)
    {
        /* @var $user User */
        $user = Yii::$app->user->getIdentity();
        $canSelectProfileCalendar = $user->moduleManager->isEnabled('calendar') || $user->moduleManager->canEnable('calendar');

        $contentContainerSelection = [];
        if ($canSelectProfileCalendar) {
            $contentContainerSelection[$user->contentcontainer_id] = Yii::t('CalendarModule.base', 'Profile Calendar');
        }

        $calendarMemberSpaceQuery = Membership::getUserSpaceQuery(Yii::$app->user->getIdentity());

        if (!ContentContainerModuleManager::getDefaultState(Space::class, 'calendar')) {
            $calendarMemberSpaceQuery->leftJoin(
                'contentcontainer_module',
                'contentcontainer_module.module_id = :calendar AND contentcontainer_module.contentcontainer_id = space.contentcontainer_id',
                [':calendar' => 'calendar'],
            )->andWhere('contentcontainer_module.module_id IS NOT NULL')
                ->andWhere(['contentcontainer_module.module_state' => ContentContainerModuleState::STATE_ENABLED]);
        }

        foreach ($calendarMemberSpaceQuery->all() as $space) {
            if ((new CalendarEntry($space))->content->canEdit()) {
                $contentContainerSelection[$space->contentcontainer_id] = $space->displayName;
            }
        }

        return $this->renderAjax('selectContainerModal', [
            'contentContainerSelection' => $contentContainerSelection,
            'canSelectProfileCalendar' => $canSelectProfileCalendar,
            'submitUrl' => Url::to(['/calendar/global/select-submit', 'start' => $start, 'end' => $end]),
        ]);
    }

    public function actionSelectSubmit($start = null, $end = null)
    {
        $this->forcePostRequest();

        $contentContainer = ContentContainer::findOne(['id' => Yii::$app->request->post('contentContainerId')]);

        if (empty($contentContainer)) {
            throw new HttpException(404);
        }

        $container = $contentContainer->getPolymorphicRelation();

        if (!(new CalendarEntry($container))->content->canEdit()) {
            throw new HttpException(403);
        }

        if ($container instanceof User && $container->is(Yii::$app->user->getIdentity())) {
            if (!$container->moduleManager->isEnabled('calendar')) {
                return Yii::$app->runAction('/calendar/global/enable', ['start' => $start, 'end' => $end]);

                /**
                 *TODO: automatically enable the calendar module in profile
                 * $container->enableModule('calendar');
                 *
                 *TODO: should be handle by the core
                 *Yii::$app->cache->get(\humhub\modules\user\models\Module::STATES_CACHE_ID_PREFIX . $container->id);
                 *Yii::$app->user->getIdentity()->_enabledModules = null;
                 **/
            }
        }

        $params = ($container instanceof User) ? ['uguid' => $container->guid] : ['sguid' => $container->guid];
        $params['start'] = $start;
        $params['end'] = $end;
        $params['cal'] = 1;

        Yii::$app->request->setQueryParams($params);

        return Yii::$app->runAction('/calendar/entry/edit', $params);
    }

    /**
     * Loads entries within search interval, the given string contains timezone offset.
     *
     * @param $start string search start time e.g: '2019-12-30T00:00:00+01:00'
     * @param $end string search end time e.g: '2020-02-10T00:00:00+01:00'
     * @return \yii\web\Response
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionLoadAjax($start, $end)
    {
        $output = [];

        if (!Yii::$app->user->isGuest) {
            $settings = $this->getUserSettings();

            $viewMode = (string) Yii::$app->request->get('viewMode', CalendarFilterBar::VIEW_MY_CALENDARS);
            $calendars = (string) Yii::$app->request->get('calendars', CalendarFilterBar::CALENDARS_ALL);
            $show = (string) Yii::$app->request->get('show', CalendarFilterBar::SHOW_ALL);
            $types = Yii::$app->request->get('types', []);

            // Only ever persist known/valid values - guards against arbitrary request values
            // ending up permanently stored in the user settings.
            if (!CalendarFilterBar::isValidViewMode($viewMode)) {
                $viewMode = CalendarFilterBar::VIEW_MY_CALENDARS;
            }
            if (!CalendarFilterBar::isValidCalendars($calendars)) {
                $calendars = CalendarFilterBar::CALENDARS_ALL;
            }
            if (!CalendarFilterBar::isValidShow($show)) {
                $show = CalendarFilterBar::SHOW_ALL;
            }
            $types = is_array($types) ? array_values(array_unique(array_filter(array_map('intval', $types)))) : [];

            $settings->set('lastViewMode', $viewMode);
            $settings->set('lastCalendars', $calendars);
            $settings->set('lastShow', $show);
            $settings->setSerialized('lastTypes', $types);

            $filters = CalendarFilterBar::getFiltersForShow($show);

            // "Entire Network" is only ever applied if explicitly requested (viewMode=network).
            // Every other/default state always resolves to a non-empty scope, so switching
            // to "all readable content" can never happen implicitly.
            if ($viewMode !== CalendarFilterBar::VIEW_NETWORK) {
                $filters['userRelated'] = CalendarFilterBar::getSelectorsForCalendars($calendars);
            }

            $entries = $this->calendarService->getCalendarItems(new DateTime($start), new DateTime($end), $filters, null, null, true, $types);
        } else {
            $entries = $this->calendarService->getCalendarItems(new DateTime($start), new DateTime($end));
        }

        foreach ($entries as $entry) {
            $output[] = FullCalendar::getFullCalendarArray($entry);
        }

        return $this->asJson($output);
    }

    public function actionEnable($start, $end)
    {
        $user = Yii::$app->user->getIdentity();

        $cancelButton = ModalButton::cancel();
        $enableButton = ModalButton::primary(Yii::t('CalendarModule.base', 'Enable'))
            ->action('content.container.enableModule', Url::toEnableProfileModule($user));

        $nextButton = ModalButton::primary(Yii::t('CalendarModule.base', 'Next'))
            ->load(Url::toCreateEntry($user, $start, $end))->cssClass('disable d-none');

        return Modal::widget([
            'title' => Yii::t('CalendarModule.base', '<strong>Add</strong> profile calendar'),
            'body' => Yii::t('CalendarModule.base', 'In order to add events to your profile, you have to enable the calendar module first.'),
            'footer' => $cancelButton . $enableButton . $nextButton,
        ]);
    }

    public function actionEnableConfig()
    {
        $user = Yii::$app->user->getIdentity();

        $cancelButton = ModalButton::cancel();
        $enableButton = ModalButton::primary(Yii::t('CalendarModule.base', 'Enable'))
            ->action('content.container.enableModule', Url::toEnableProfileModule($user));

        $nextButton = ModalButton::primary(Yii::t('CalendarModule.base', 'Next'))
            ->link(Url::toConfig($user))
            ->cssClass('disable d-none')
            ->close();

        return Modal::widget([
            'title' => Yii::t('CalendarModule.base', '<strong>Add</strong> profile calendar'),
            'body' => Yii::t('CalendarModule.base', 'Do you want to install this module on your profile?'),
            'footer' => $cancelButton . $enableButton . $nextButton,
        ]);
    }

    public function actionUpdateMonthlyRecurrenceSelection($date)
    {
        $dummy = new CalendarEntryDummy(['start' => CalendarUtils::parseDateTimeString($date, null, null, null)]);
        $recurrenceForm = new RecurrenceFormModel(['entry' => $dummy]);
        return $this->asJson(['result' => $recurrenceForm->getMonthDaySelection()]);
    }

    public function actionFindFilterTypes($keyword)
    {
        return $this->asJson(FilterType::search($keyword, null, true));
    }
}
