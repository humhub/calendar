<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models\forms;

use humhub\components\SettingsManager;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\Module;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerSettingsManager;
use Yii;
use yii\base\Model;

class BasicSettings extends Model
{
    public const SETTING_CONTENT_HIDDEN = 'defaults.contentHidden';
    public const SETTING_BIRTHDAY_SHOW_ALL = 'defaults.birthdayShowAll';

    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var SettingsManager
     */
    private $settings;

    /**
     * @var bool Default setting to hide calendar entry on stream
     */
    public $contentHiddenDefault;

    /**
     * @var bool Global setting to show birthdays of all readable users in the calendar
     * regardless of the currently selected "Calendars" filter (e.g. also for users
     * that are not followed). Only configurable on global level.
     */
    public $birthdayShowToEveryone;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->initSettings();
    }

    private function initSettings()
    {
        $this->contentHiddenDefault = (bool) $this->getSetting(self::SETTING_CONTENT_HIDDEN, false);

        if ($this->isGlobal()) {
            $this->birthdayShowToEveryone = (bool) $this->getSettings()->get(self::SETTING_BIRTHDAY_SHOW_ALL, false);
        }
    }

    /**
     * Returns either the inherited value of $key in case a contentContainer is set or the global value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getSetting(string $key, $default = null)
    {
        return $this->contentContainer
            ? $this->getSettings()->getInherit($key, $default)
            : $this->getSettings()->get($key, $default);
    }

    /**
     * @return SettingsManager|ContentContainerSettingsManager
     */
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
            [['contentHiddenDefault'], 'boolean'],
            [['birthdayShowToEveryone'], 'boolean'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'birthdayShowToEveryone' => Yii::t('CalendarModule.config', 'Show birthdays regardless of the selected calendar filter'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'birthdayShowToEveryone' => Yii::t('CalendarModule.config', 'By default birthdays are only shown for your own profile and for followed users. If activated, birthdays of all otherwise visible users are shown in the calendar and dashboard snippet, no matter which "Calendars" filter is currently selected.'),
        ];
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->getSettings()->set(self::SETTING_CONTENT_HIDDEN, $this->contentHiddenDefault);

        if ($this->isGlobal()) {
            $this->getSettings()->set(self::SETTING_BIRTHDAY_SHOW_ALL, $this->birthdayShowToEveryone);
        }

        return true;
    }

    public function reset()
    {
        $this->getSettings()->set(self::SETTING_CONTENT_HIDDEN, null);
        if ($this->isGlobal()) {
            $this->getSettings()->set(self::SETTING_BIRTHDAY_SHOW_ALL, null);
        }
        $this->initSettings();
    }

    public function isGlobal(): bool
    {
        return $this->contentContainer === null;
    }

    public function showResetButton(): bool
    {
        return $this->getSettings()->get(self::SETTING_CONTENT_HIDDEN) !== null
            || ($this->isGlobal() && $this->getSettings()->get(self::SETTING_BIRTHDAY_SHOW_ALL) !== null);
    }

    public function getResetButtonUrl(): string
    {
        return Url::toBasicSettingsReset($this->contentContainer);
    }
}
