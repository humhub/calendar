<?php


namespace humhub\modules\calendar\interfaces\event;


use humhub\components\SettingsManager;
use Yii;
use yii\base\Model;

/**
 * Class CalendarTypeArrayWrapper
 * @deprecated Used for deprecated array based calendar interface (prior to v1.0.0)
 */
class CalendarTypeArrayWrapper extends Model implements CalendarTypeIF
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
     * @var string description
     */
    const OPTION_DESCRIPTION = 'description';

    /**
     * @var string Icon type key
     */
    const OPTION_KEY = 'key';

    /**
     * @var string Title option key
     */
    const OPTION_TITLE = 'title';

    public $options = [];

    public $key;

    /**
     * @return string The options default color or fallback color if not color was defined.
     */
    public function getDefaultColor()
    {
        if(!empty($this->options) && isset($this->options[static::OPTION_DEFAULT_COLOR])) {
            return $this->options[static::OPTION_DEFAULT_COLOR];
        }

        return null;
    }

    /**
     * @return string returns the options title
     */
    public function getTitle()
    {
        if(!empty($this->options) && isset($this->options[static::OPTION_TITLE])) {
            return $this->options[static::OPTION_TITLE];
        }

        return null;
    }

    public function getIcon()
    {
        if(!empty($this->options) && isset($this->options[static::OPTION_ICON])) {
            return $this->options[static::OPTION_ICON];
        }

        return null;
    }

    public function getDescription()
    {
        if(!empty($this->options) && isset($this->options[static::OPTION_DESCRIPTION])) {
            return $this->options[static::OPTION_DESCRIPTION];
        }

        return null;
    }

    public function getKey()
    {
        if($this->key) {
            return $this->key;
        }

        if(!empty($this->options) && isset($this->options[static::OPTION_KEY])) {
            return $this->options[static::OPTION_KEY];
        }

        return null;
    }
}