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
 * Time: 17:13
 */

namespace humhub\modules\calendar\interfaces;


use DateTime;
use humhub\widgets\Label;
use DateTimeInterface;

interface CalendarEventIF
{
    /**
     * Used when the detail view should be opened within a modal
     */
    const VIEW_MODE_MODAL = 'modal';

    /**
     * Used when the detail view should be opened within a new tab
     */
    const VIEW_MODE_BLANK = 'blank';

    /**
     * Used when the detail view should be opened by redirect link
     */
    const VIEW_MODE_REDIRECT = 'redirect';

    /**
     * @return string a unique id for this event, used e.g. for ICal exports. Can be automatically created when using [AbstractCalendarQuery].
     * @see AbstractCalendarQuery::$autoAssignUid
     */
    public function getUid();

    /**
     * @return CalendarTypeIF instance of the related calendar type
     */
    public function getType();


    /**
     * @return boolean weather or not this item spans exactly over a whole day
     */
    public function isAllDay();

    /**
     * @return DateTimeInterface start datetime object of this calendar item, ideally with timezone information
     */
    public function getStartDateTime();

    /**
     * @return DateTimeInterface end datetime object of this calendar item, ideally with timezone information
     */
    public function getEndDateTime();

    /**
     * @return string  The timezone string of this item.
     */
    public function getTimezone();

    /**
     * @return string
     */
    public function getUrl();

    /**
     * Access url of the source content or other view
     *
     * @return string the timezone this item was originally saved, note this is
     */
    public function getCalendarViewUrl();

    /**
     * @return string view mode 'modal', 'blank', 'redirect'
     */
    public function getCalendarViewMode();

    /**
     * Access url of the source content or other view
     *
     * @return string the timezone this item was originally saved, note this is
     */
    public function getUpdateUrl();

    /**
     * Check if this calendar entry is editable, for example by checking `$this->content->canEdit()`.
     *
     * @return bool true if this entry is editable, false
     */
    public function isEditable();

    /**
     * @return string hex color string e.g: '#44B5F6'
     */
    public function getColor();

    /**
     * Access url of the source content or other view
     *
     * @return string the timezone this item was originally saved, note this is
     */
    public function getTitle();

    /**
     * @return string|null
     */
    public function getLocation();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return Label|string|null
     */
    public function getBadge();

    /**
     * @return string|null
     */
    public function getIcon();

    /**
     * Additional configuration options
     * @return array
     */
    public function getCalendarOptions();
}