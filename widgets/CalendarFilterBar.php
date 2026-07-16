<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\helpers\ContentContainerHelper;
use humhub\modules\content\models\ContentTag;
use Yii;

/**
 * Class CalendarFilterBar
 *
 * Renders the calendar filter/selector bar as a set of single-select dropdowns, styled
 * consistently with `humhub\modules\ui\widgets\DirectoryFilters` (used by the People and
 * Space directories): one dropdown per filter dimension plus a reset ("x") action, all
 * refreshing the calendar via AJAX without a page reload.
 *
 * The "Calendars" selector replaces the former multi-checkbox scope selection. Choosing
 * less can never implicitly widen the result set anymore - "Entire network" is only ever
 * reached by explicitly picking it in the "View" dropdown.
 *
 * @package humhub\modules\calendar\widgets
 */
class CalendarFilterBar extends Widget
{
    /** View mode: only the users own related calendars (default) */
    public const VIEW_MY_CALENDARS = 'mycalendars';
    /** View mode: explicit opt-in to show all readable events of the network */
    public const VIEW_NETWORK = 'network';

    public const CALENDARS_ALL = 'all';
    public const CALENDARS_PROFILE = 'profile';
    public const CALENDARS_SPACES = 'spaces';
    public const CALENDARS_FOLLOWED_SPACES = 'followedspaces';
    public const CALENDARS_FOLLOWED_USERS = 'followedusers';

    public const SHOW_ALL = '';
    public const SHOW_PARTICIPATE = 'participate';
    public const SHOW_MINE = 'mine';

    /**
     * @var string current view mode, one of self::VIEW_*
     */
    public $view = self::VIEW_MY_CALENDARS;

    /**
     * @var string current calendars scope, one of self::CALENDARS_*
     */
    public $calendars = self::CALENDARS_ALL;

    /**
     * @var string current "show only" filter, one of self::SHOW_*
     */
    public $show = self::SHOW_ALL;

    /**
     * @var int[] currently selected CalendarEntryType ids ("Event types" filter)
     */
    public array $types = [];

    public bool $showFilter = true;
    public bool $showSelectors = true;
    public bool $showTypes = true;

    /**
     * @inheritdoc
     */
    public function beforeRun()
    {
        return parent::beforeRun() && !Yii::$app->user->isGuest;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $currentContentContainer = ContentContainerHelper::getCurrent();
        $typesQuery = ContentTag::find()->where(['type' => CalendarEntryType::class]);
        if ($currentContentContainer) {
            $typesQuery->andWhere(['contentcontainer_id' => $currentContentContainer->contentcontainer_id]);
        }
        $this->showTypes = $this->showTypes && $typesQuery->exists();

        $typeSelection = [];
        if ($this->showTypes && !empty($this->types)) {
            $typeSelection = CalendarEntryType::find()->where(['id' => $this->types])->all();
        }

        return $this->render('calendarFilterBar', [
            'view' => $this->view,
            'calendars' => $this->calendars,
            'show' => $this->show,
            'typeSelection' => $typeSelection,
            'viewOptions' => self::getViewOptions(),
            'calendarsOptions' => self::getCalendarsOptions(),
            'showOptions' => self::getShowOptions(),
            'showFilters' => $this->showFilter,
            'showSelectors' => $this->showSelectors,
            'showTypes' => $this->showTypes,
            'isFiltered' => $this->isFiltered(),
        ]);
    }

    /**
     * @return array<string, string> options for the "View" dropdown
     */
    public static function getViewOptions(): array
    {
        return [
            self::VIEW_MY_CALENDARS => Yii::t('CalendarModule.views', 'My Calendars'),
            self::VIEW_NETWORK => Yii::t('CalendarModule.views', 'Entire network'),
        ];
    }

    /**
     * @return array<string, string> options for the "Show only" dropdown
     */
    public static function getShowOptions(): array
    {
        return [
            self::SHOW_ALL => Yii::t('CalendarModule.views', 'Select...'),
            self::SHOW_PARTICIPATE => Yii::t('CalendarModule.views', "I'm attending"),
            self::SHOW_MINE => Yii::t('CalendarModule.views', 'My events'),
        ];
    }

    /**
     * Maps the individual (non "all") "Calendars" dropdown values to the
     * ActiveQueryContent::USER_RELATED_SCOPE_* scopes they represent. Only includes the
     * options that are actually available for the current user (e.g. no "My profile" entry
     * if the calendar module can't be enabled on the profile, no follow related entries if
     * following is disabled network wide).
     *
     * @return array<string, int[]>
     */
    public static function getCalendarsScopes(): array
    {
        $scopes = [];

        if (
            !Yii::$app->user->isGuest
            && Yii::$app->user->identity->moduleManager->isEnabled('calendar')
        ) {
            $scopes[self::CALENDARS_PROFILE] = [ActiveQueryContent::USER_RELATED_SCOPE_OWN_PROFILE];
        }

        $scopes[self::CALENDARS_SPACES] = [ActiveQueryContent::USER_RELATED_SCOPE_SPACES];

        if (!Yii::$app->getModule('user')->disableFollow) {
            $scopes[self::CALENDARS_FOLLOWED_SPACES] = [ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_SPACES];
            $scopes[self::CALENDARS_FOLLOWED_USERS] = [ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS];
        }

        return $scopes;
    }

    /**
     * @return array<string, string> options for the "Calendars" dropdown
     */
    public static function getCalendarsOptions(): array
    {
        $labels = [
            self::CALENDARS_PROFILE => Yii::t('CalendarModule.views', 'My profile'),
            self::CALENDARS_SPACES => Yii::t('CalendarModule.views', 'My spaces'),
            self::CALENDARS_FOLLOWED_SPACES => Yii::t('CalendarModule.views', 'Spaces I follow'),
            self::CALENDARS_FOLLOWED_USERS => Yii::t('CalendarModule.views', 'People I follow'),
        ];

        $options = [self::CALENDARS_ALL => Yii::t('CalendarModule.views', 'All')];

        foreach (self::getCalendarsScopes() as $key => $scope) {
            $options[$key] = $labels[$key];
        }

        return $options;
    }

    /**
     * Resolves a "Calendars" dropdown value into the ActiveQueryContent::USER_RELATED_SCOPE_*
     * scopes it should filter by. "all" (the default) resolves to the union of all available
     * scopes, i.e. profile + spaces + followed spaces/users - never to an empty/unrestricted
     * scope. Unknown or unavailable values safely fall back to "all" as well.
     *
     * @param string $calendars one of self::CALENDARS_*
     * @return int[]
     */
    public static function getSelectorsForCalendars(string $calendars): array
    {
        $scopesMap = self::getCalendarsScopes();

        if ($calendars !== self::CALENDARS_ALL && isset($scopesMap[$calendars])) {
            return $scopesMap[$calendars];
        }

        return empty($scopesMap) ? [] : array_merge(...array_values($scopesMap));
    }

    /**
     * Resolves a "Show only" dropdown value into the corresponding CalendarEntry FILTER_* constant.
     *
     * @param string $show one of self::SHOW_*
     * @return int[]
     */
    public static function getFiltersForShow(string $show): array
    {
        return match ($show) {
            self::SHOW_PARTICIPATE => [CalendarEntry::FILTER_PARTICIPATE],
            self::SHOW_MINE => [CalendarEntry::FILTER_MINE],
            default => [],
        };
    }

    /**
     * @return bool whether the current selection deviates from the defaults
     */
    public function isFiltered(): bool
    {
        return $this->view !== self::VIEW_MY_CALENDARS
            || $this->calendars !== self::CALENDARS_ALL
            || $this->show !== self::SHOW_ALL
            || !empty($this->types);
    }
}
