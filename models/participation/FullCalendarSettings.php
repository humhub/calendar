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
    public const SETTING_LIST_MONTH = 'listMonth';
    public const SETTING_VIEW_MODE_KEY = 'defaults.fullCalendarViewMode';

    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var string
     */
    public $viewMode;

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

        // Handle removed view modes
        if ($this->viewMode === 'listWeek' || $this->viewMode === 'listYear') {
            $this->viewMode = 'listMonth';
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
            [['viewMode'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'viewMode' => Yii::t('CalendarModule.config', 'View mode'),
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $settings = $this->getSettings();
        $settings->set(self::SETTING_VIEW_MODE_KEY, $this->viewMode);
        return true;
    }

    public function reset()
    {
        $settings = $this->getSettings();
        $settings->set(self::SETTING_VIEW_MODE_KEY, null);
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
            self::SETTING_LIST_MONTH => Yii::t('CalendarModule.base', 'List'),
        ];
    }
}
