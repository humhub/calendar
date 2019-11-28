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
 * Time: 17:16
 */

namespace humhub\modules\calendar\interfaces;


use humhub\widgets\Label;
use Yii;
use \DateTime;
use yii\base\Model;

/**
 * Class CalendarEventIFWrapper
 * @package humhub\modules\calendar\interfaces
 * @deprecated Used for deprecated array based calendar interface (prior to v1.0.0)
 */
class CalendarEventIFWrapper extends Model implements CalendarEventIF
{
    const OPTION_START = 'start';
    const OPTION_END = 'end';
    const OPTION_TITLE = 'title';
    const OPTION_COLOR = 'color';
    const OPTION_ALL_DAY = 'allDay';
    const OPTION_UPDATE_URL = 'updateUrl';
    const OPTION_VIEW_URL = 'viewUrl';
    const OPTION_VIEW_MODE = 'viewMode';
    const OPTION_OPEN_URL = 'openUrl';
    const OPTION_ICON = 'icon';
    const OPTION_BADGE = 'badge';
    const OPTION_EDITABLE = 'editable';
    const OPTION_TIMEZONE = 'timezone';
    const OPTION_UID = 'uid';
    const OPTION_RRULE = 'rrule';
    const OPTION_EXDATE = 'exdate';
    const OPTION_LOCATION = 'location';
    const OPTION_DESCRIPTION = 'description';

    /**
     * @var CalendarTypeIF
     */
    public $itemType;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @inheritdoc
     */
    public function getStartDateTime()
    {
        return $this->getOption(static::OPTION_START, new DateTime());
    }

    /**
     * @inheritdoc
     */
    public function getEndDateTime()
    {
        return $this->getOption(static::OPTION_END, new DateTime());
    }

    /**
     * @inheritdoc
     */
    public function getTimezone()
    {
        return $this->getOption(static::OPTION_TIMEZONE, Yii::$app->timeZone);
    }

    public function isEditable()
    {
        return $this->getOption(static::OPTION_EDITABLE, false);
    }

    public function getTitle()
    {
        return $this->getOption(static::OPTION_TITLE, $this->itemType ? $this->itemType->getTitle() : '');
    }

    public function getRrule()
    {
        return $this->getOption(static::OPTION_RRULE, null);
    }

    public function getExdate()
    {
        return $this->getOption(static::OPTION_EXDATE, null);
    }

    public function getColor()
    {
        return $this->getOption(static::OPTION_COLOR, $this->itemType ? $this->itemType->getColor() : '');
    }

    public function isAllDay()
    {
        $this->getOption(static::OPTION_ALL_DAY,  false);
    }

    public function getUpdateUrl()
    {
        return $this->getOption(static::OPTION_UPDATE_URL, null);
    }

    protected function getViewMode()
    {
        return $this->getOption(static::OPTION_VIEW_MODE, static::VIEW_MODE_MODAL);
    }

    protected function getViewUrl()
    {
        return $this->getOption(static::OPTION_VIEW_URL, null);
    }

    protected function getOption($key, $default, $options = null)
    {
        $options = empty($options) ? $this->options : $options;
        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->getOption(static::OPTION_OPEN_URL, null);
    }

    /**
     * @inheritdoc
     */
    public function getBadge()
    {
        $default = $this->itemType ? Label::asColor($this->getColor(), $this->itemType->getTitle())->icon($this->getIcon())->right() : '';
        return $this->getOption(static::OPTION_BADGE, $default);
    }

    /**
     * @inheritdoc
     */
    public function getIcon()
    {
        return $this->getOption(static::OPTION_ICON, $this->itemType ? $this->itemType->getIcon() : null);
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->getOption(static::OPTION_UID, null);
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->getOption(static::OPTION_LOCATION, true);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getOption(static::OPTION_DESCRIPTION, true);
    }

    /**
     * Access url of the source content or other view
     *
     * @return string the timezone this item was originally saved, note this is
     */
    public function getCalendarViewUrl()
    {
        return $this->getUrl();
    }

    /**
     * @return string view mode 'modal', 'blank', 'redirect'
     */
    public function getCalendarViewMode()
    {
        return $this->getViewMode();
    }

    /**
     * Additional configuration options
     * @return array
     */
    public function getCalendarOptions()
    {
        return [];
    }

    /**
     * @return CalendarTypeIF
     */
    public function getType()
    {
        return $this->itemType;
    }
}