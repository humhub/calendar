<?php

namespace humhub\modules\calendar\models\participation;

use humhub\components\SettingsManager;
use humhub\modules\calendar\Module;
use humhub\modules\content\components\ContentContainerActiveRecord;
use Yii;
use yii\base\Model;

class FullCalendarSettings extends Model
{
    public const SETTING_GRID_DAY = 'timeGridDay';
    public const SETTING_GRID_WEEK = 'timeGridWeek';
    public const SETTING_GRID_MONTH = 'dayGridMonth';
    public const SETTING_LIST = 'list';
    public const SETTING_VIEW_MODE_KEY = 'defaults.fullCalendarViewMode';
    public const SETTING_LIST_VIEW_TYPE_KEY = 'listViewType';
    public const LIST_VIEW_WEEK = 'listWeek';
    public const LIST_VIEW_MONTH = 'listMonth';
    public const LIST_VIEW_YEAR = 'listYear';

    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var string
     */
    public $viewMode;

    /**
     * @var string
     */
    public $listViewType;

    /**
     * @var SettingsManager
     */
    private $settings;


    public function init()
    {
        parent::init();
        $this->initSettings();
    }

    private function initSettings()
    {
        $this->viewMode = $this->getSetting(self::SETTING_VIEW_MODE_KEY, static::SETTING_GRID_MONTH);

        $this->listViewType = $this->getSetting(self::SETTING_LIST_VIEW_TYPE_KEY, static::LIST_VIEW_MONTH);

        // if default viewMode is "list" (or old entry like "listMonth"), take the configured list view type
        if (substr($this->viewMode, 0, 4) == 'list') {
            $this->viewMode === $this->listViewType;
        }
    }

    /**
     * Returns either the inherited value of $key in case a contentCotnainer is set or the global value.
     * @return mixed
     */
    protected function getSetting($key, $default = null)
    {
        return $this->contentContainer ? $this->getSettings()->getInherit($key, $default) : $this->getSettings()->get($key, $default);
    }

    private function getSettings()
    {
        if (!$this->settings) {
            /* @var $module Module */
            $module = Yii::$app->getModule('calendar');
            $this->settings = $this->contentContainer ? $module->settings->contentContainer($this->contentContainer) : $module->settings;
        }

        return $this->settings;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['viewMode', 'listViewType'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'viewMode' => Yii::t('CalendarModule.config', 'View mode'),
            'listViewType' => Yii::t('CalendarModule.config', 'List view type'),
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $settings = $this->getSettings();
        $settings->set(self::SETTING_VIEW_MODE_KEY, $this->viewMode);
        $settings->set(self::SETTING_LIST_VIEW_TYPE_KEY, $this->listViewType);
        return true;
    }

    public function reset()
    {
        $settings = $this->getSettings();
        $settings->set(self::SETTING_VIEW_MODE_KEY, null);
        $settings->set(self::SETTING_LIST_VIEW_TYPE_KEY, null);
        $this->initSettings();
    }

    public function isGlobal()
    {
        return $this->contentContainer === null;
    }

    public function getViewModeItems()
    {
        return [
            self::SETTING_GRID_MONTH => Yii::t('CalendarModule.base', 'Month'),
            self::SETTING_GRID_WEEK => Yii::t('CalendarModule.base', 'Week'),
            self::SETTING_GRID_DAY => Yii::t('CalendarModule.base', 'Day'),
            self::SETTING_LIST => Yii::t('CalendarModule.base', 'List'),
        ];
    }

    public function getListViewTypes()
    {
        return [
            self::LIST_VIEW_WEEK => Yii::t('CalendarModule.base', 'Week'),
            self::LIST_VIEW_MONTH => Yii::t('CalendarModule.base', 'Month'),
            self::LIST_VIEW_YEAR => Yii::t('CalendarModule.base', 'Year'),
        ];
    }
}
