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
        ];
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $settings = $this->getSettings();
        $settings->set(self::SETTING_CONTENT_HIDDEN, $this->contentHiddenDefault);

        return true;
    }

    public function reset()
    {
        $settings = $this->getSettings();
        $settings->set(self::SETTING_CONTENT_HIDDEN, null);
        $this->initSettings();
    }

    public function isGlobal(): bool
    {
        return $this->contentContainer === null;
    }

    public function showResetButton(): bool
    {
        return $this->getSettings()->get(self::SETTING_CONTENT_HIDDEN) !== null;
    }

    public function getResetButtonUrl(): string
    {
        return Url::toBasicSettingsReset($this->contentContainer);
    }
}
