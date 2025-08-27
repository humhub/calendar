<?php

namespace humhub\modules\calendar\models\participation;

use humhub\components\SettingsManager;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\Module;
use humhub\modules\content\components\ContentContainerActiveRecord;
use Yii;
use yii\base\Model;

class ParticipationSettings extends Model
{
    public const SETTING_PARTICIPATION_MODE = 'defaults.participationMode';
    public const SETTING_ALLOW_MAYBE = 'defaults.allowMaybe';
    public const SETTING_ALLOW_DECLINE = 'defaults.allowDecline';

    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var int
     */
    public $participation_mode;

    /**
     * @var int
     */
    public $allow_decline;

    /**
     * @var SettingsManager
     */
    private $settings;

    /**
     * @var int
     */
    public $allow_maybe;


    public function init()
    {
        parent::init();
        $this->initSettings();
    }

    private function initSettings()
    {
        $this->participation_mode = (int) $this->getSetting(self::SETTING_PARTICIPATION_MODE, CalendarEntryParticipation::PARTICIPATION_MODE_ALL);
        $this->allow_decline = (int) $this->getSetting(self::SETTING_ALLOW_DECLINE, 1);
        $this->allow_maybe = (int) $this->getSetting(self::SETTING_ALLOW_MAYBE, 1);
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
            [['allow_decline', 'allow_maybe'], 'integer'],
            [['participation_mode'], 'in', 'range' => CalendarEntryParticipation::$participationModes],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $entry = new CalendarEntry();
        return $entry->attributeLabels();
    }

    /**
     * @return bool checks if participation should be allowed by default
     */
    public function isParticipationAllowed()
    {
        return $this->participation_mode != CalendarEntryParticipation::PARTICIPATION_MODE_NONE;
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $settings = $this->getSettings();
        $settings->set(self::SETTING_PARTICIPATION_MODE, $this->participation_mode);
        $settings->set(self::SETTING_ALLOW_MAYBE, $this->allow_maybe);
        $settings->set(self::SETTING_ALLOW_DECLINE, $this->allow_decline);
        return true;
    }

    public function reset()
    {
        $settings = $this->getSettings();
        $settings->set(self::SETTING_PARTICIPATION_MODE, null);
        $settings->set(self::SETTING_ALLOW_MAYBE, null);
        $settings->set(self::SETTING_ALLOW_DECLINE, null);
        $this->initSettings();
    }

    public function isGlobal()
    {
        return $this->contentContainer === null;
    }

    public function showResetButton()
    {
        return $this->getSettings()->get(self::SETTING_PARTICIPATION_MODE) !== null;
    }

    public function getResetButtonUrl()
    {
        return Url::toParticipationSettingsReset($this->contentContainer);
    }
}
