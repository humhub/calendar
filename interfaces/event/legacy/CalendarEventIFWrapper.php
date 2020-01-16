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

namespace humhub\modules\calendar\interfaces\event\legacy;


use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\event\CalendarTypeIF;
use humhub\modules\calendar\interfaces\fullcalendar\FullCalendarEventIF;
use humhub\widgets\Label;
use Yii;
use \DateTime;
use yii\base\Model;

/**
 * Class CalendarEventIFWrapper
 * @package humhub\modules\calendar\interfaces
 * @deprecated Used for deprecated array based calendar interface (prior to v1.0.0)
 */
class CalendarEventIFWrapper extends Model implements CalendarEventIF, FullCalendarEventIF
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
    const OPTION_LAST_MODIFIED = 'lastModified';
    const OPTION_SEQUENCE = 'sequence';

    /**
     * @var DummyEventQuery
     */
    private static $query;

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
        return $this->getOption(static::OPTION_ALL_DAY,  false);
    }

    public function getUpdateUrl()
    {
        return $this->getOption(static::OPTION_UPDATE_URL, null);
    }

    protected function getViewMode()
    {
        return $this->getOption(static::OPTION_VIEW_MODE, static::VIEW_MODE_REDIRECT);
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
    public function getEventType()
    {
        return $this->itemType;
    }

    /**
     * Used for example in ICal exports. In case of ContentActiveRecords a common implementation would be:
     *
     * ```php
     * public function getLastModified()
     * {
     *     return new DateTime($this->content->updated_at);
     * }
     * ```
     * @return DateTime|null
     */
    public function getLastModified()
    {
        return $this->getOption(static::OPTION_LAST_MODIFIED, null);
    }

    /**
     * Add additional options supported by fullcalnedar: https://fullcalendar.io/docs/event-object
     * @return array
     */
    public function getFullCalendarOptions()
    {
        return [];
    }

    /**
     * Optional sequence support see https://www.kanzaki.com/docs/ical/sequence.html
     *
     * This function should return null in case sequences is not supported.
     *
     * @return int
     */
    public function getSequence()
    {
        return $this->getOption(static::OPTION_SEQUENCE, null);
    }

    /**
     * The timezone string of the end date.
     * In case the start and end timezone is the same, this function can return null.
     *
     * @return string
     */
    public function getEndTimezone()
    {
        // TODO: Implement getEndTimezone() method.
    }

    /**
     * Used to update and persist the start and end datetime of this event. This function has to be implemented in case
     * `isUpdatable()` calendar view updates should be supported.
     *
     * When using the default update action the $start and $end date are provided in system timezone.
     * When using a custom action defined as `updateUrl`, the dates have to be translated to system timezone manually.
     *
     * This function should implement all necessary validation. The default update action will automatically
     * validate `$event->isUpdatable()` in order to check if the current user is allowed to edit the event.
     *
     * In case the event could not be saved this function should return an error message string or false otherwise true.
     *
     * @param DateTime $start
     * @param DateTime $end
     * @return boolean|string
     */
    public function updateTime(DateTime $start, DateTime $end)
    {
        return false;
    }

    public function isUpdatable()
    {
        return $this->getOption(static::OPTION_EDITABLE, false);
    }
}