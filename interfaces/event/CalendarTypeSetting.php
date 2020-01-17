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

namespace humhub\modules\calendar\interfaces\event;


use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\space\models\Space;
use Yii;
use yii\base\Model;
use humhub\modules\calendar\helpers\Url;
use humhub\components\SettingsManager;
use humhub\modules\content\components\ContentContainerActiveRecord;

/**
 * Instances of this class represent an event type of a specific container and can be used
 * to overwrite default values of the related CalendarType as color or enable/disable this type on
 * the container.
 *
 * If no container is given, this
 */
class CalendarTypeSetting extends Model implements CalendarTypeIF
{
    const COLOR_VALIDATON_PATTERN = '/^#(?:[0-9a-fA-F]{3}){1,2}$/';

    /**
     * Fallback color used in case no default color was provided
     */
    const COLOR_FALLBACK = '#44B5F6';

    /**
     * @var CalendarTypeIF
     */
    public $type;

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
            ['color', 'match', 'pattern' => static::COLOR_VALIDATON_PATTERN],
        ];
    }

    /**
     * Checks if the given item type is enabled for this
     */
    public function isEnabled()
    {
        $settingKey = $this->getKey().'_item_enabled';
        if($this->contentContainer) {
            return (boolean) $this->getSettings()->contentContainer($this->contentContainer)->getInherit($settingKey, true);
        }

        return (boolean) $this->getSettings()->get($settingKey, true);
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
        $settingKey = $this->getKey().'_item_enabled';
        if($this->contentContainer) {
            return $this->getSettings()->contentContainer($this->contentContainer)->set($settingKey, $isEnabled);
        }

        return $this->getSettings()->set($settingKey, $isEnabled);
    }

    /**
     * @return string The options default color or fallback color if not color was defined.
     */
    public function getDefaultColor()
    {
        if($this->type->getDefaultColor()) {
            return $this->type->getDefaultColor();
        }

        return static::COLOR_FALLBACK;
    }

    /**
     * @return string returns the options title
     */
    public function getTitle()
    {
        return $this->type->getTitle();
    }

    public function getIcon()
    {
        return $this->type->getIcon();
    }

    /**
     * @return bool returns the currently configured color for the given ContentContainerActiveRecord
     */
    public function getColor()
    {
        $settingKey = $this->getKey().'_item_color';
        if($this->contentContainer) {
            return $this->getSettings()->contentContainer($this->contentContainer)->getInherit($settingKey, $this->getDefaultColor());
        }

        return $this->getSettings()->get($settingKey, $this->getDefaultColor());
    }

    /**
     * @param $color string the new color to set as hex representation
     */
    public function updateColor($color)
    {
        $settingKey = $this->getKey().'_item_color';
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

    public function canBeDisabled()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getEditUrl()
    {
        return Url::toEditItemType($this, $this->contentContainer);
    }

    public function getKey()
    {
        return $this->type->getKey();
    }

    public function getDescription()
    {
        return $this->type->getDescription();
    }
}