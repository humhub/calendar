<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 14.09.2017
 * Time: 14:51
 */

namespace humhub\modules\calendar\interfaces;


use Yii;
use yii\base\Model;
use yii\helpers\Url;
use humhub\components\SettingsManager;
use humhub\modules\content\components\ContentContainerActiveRecord;

class CalendarItemType extends Model
{
    /**
     * @var string Color option key
     */
    const OPTION_DEFAULT_COLOR = 'color';

    /**
     * @var string Icon option key
     */
    const OPTION_ICON = 'icon';

    /**
     * @var string Title option key
     */
    const OPTION_TITLE = 'title';

    /**
     * @var string
     */
    const OPTION_ALL_DAY = 'allDay';

    /**
     * Fallback color used in case no default color was provided
     */
    const COLOR_FALLBACK = '#44B5F6';

    /**
     * @var array item options
     */
    public $options = [];

    /**
     * @var string calendar item key defined by the related module
     */
    public $key;

    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var string color to be saved
     */
    public $color;

    /**
     * @var string color to be saved
     */
    public $enabled;

    public function init()
    {
        $this->color = $this->getColor();
        $this->enabled = $this->isEnabled();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['color', 'required'],
            ['enabled', 'integer', 'min' => 0, 'max' => 1],
            ['color', 'match', 'pattern' => '/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
        ];
    }

    /**
     * Checks if the given item type is enabled for this
     */
    public function isEnabled()
    {
        $settingKey = $this->key.'_item_enabled';
        if($this->contentContainer) {
            return (boolean) $this->getSettings()->contentContainer($this->contentContainer)->getInherit($settingKey, true);
        } else {
            return (boolean) $this->getSettings()->get($settingKey, true);
        }
    }

    /**
     * @return SettingsManager
     */
    protected function getSettings()
    {
        return Yii::$app->getModule('calendar')->settings;
    }

    public function updateEnabled($isEnabled)
    {
        $settingKey = $this->key.'_item_enabled';
        if($this->contentContainer) {
            return $this->getSettings()->contentContainer($this->contentContainer)->set($settingKey, $isEnabled);
        } else {
            return $this->getSettings()->set($settingKey, $isEnabled);
        }
    }

    /**
     * @return string The options default color or fallback color if not color was defined.
     */
    public function getDefaultColor()
    {
        if(!empty($this->options) && isset($this->options[static::OPTION_DEFAULT_COLOR])) {
            return $this->options[static::OPTION_DEFAULT_COLOR];
        }

        return static::COLOR_FALLBACK;
    }

    /**
     * @return string returns the options title
     */
    public function getTitle()
    {
        if(!empty($this->options) && isset($this->options[static::OPTION_TITLE])) {
            return $this->options[static::OPTION_TITLE];
        }

        return $this->key;
    }

    /**
     * @return bool returns the currently configured color for the given ContentContainerActiveRecord
     */
    public function getColor()
    {
        $settingKey = $this->key.'_item_color';
        if($this->contentContainer) {
            return $this->getSettings()->contentContainer($this->contentContainer)->getInherit($settingKey, $this->getDefaultColor());
        } else {
            return $this->getSettings()->get($settingKey, $this->getDefaultColor());
        }
    }

    public function getIcon()
    {
        if(!empty($this->options) && isset($this->options[static::OPTION_ICON])) {
            return $this->options[static::OPTION_ICON];
        }

        return null;
    }

    /**
     * @param $color string the new color to set as hex representation
     */
    public function updateColor($color)
    {
        $settingKey = $this->key.'_item_color';
        if($this->contentContainer) {
            return $this->getSettings()->contentContainer($this->contentContainer)->set($settingKey, $color);
        } else {
            return $this->getSettings()->set($settingKey, $color);
        }
    }

    public function save()
    {
        if($this->validate()) {
            $this->updateColor($this->color);
            $this->updateEnabled($this->enabled);
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getEditUrl()
    {
        if($this->contentContainer) {
            return $this->contentContainer->createUrl('/calendar/container-config/edit-calendars', ['key' => $this->key]);
        } else {
            return Url::to(['/calendar/config/edit-calendars', 'key' => $this->key]);
        }
    }

    public function isAllDay()
    {
        if(!empty($this->options) && isset($this->options[static::OPTION_ALL_DAY])) {
            return $this->options[static::OPTION_ALL_DAY];
        }

        return false;
    }
}