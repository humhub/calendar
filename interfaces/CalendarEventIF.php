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

interface CalendarEventIF
{
    const VIEW_MODE_MODAL = 'modal';
    const VIEW_MODE_BLANK = 'blank';
    const VIEW_MODE_REDIRECT = 'redirect';

    /**
     * @return string
     */
    public function getUid();


    /**
     * @return boolean weather or not this item spans exactly over a whole day
     */
    public function isAllDay();

    /**
     * @return DateTime start datetime object of this calendar item, ideally with timezone information
     */
    public function getStartDateTime();

    /**
     * @return DateTime end datetime object of this calendar item, ideally with timezone information
     */
    public function getEndDateTime();

    /**
     * The timezone this item was originally saved. Note this has not to be the same timezone as getStartDateTime/getEndDateTime,
     * and is merely used for rendering the original date of all_day dates with timeZone information.
     *
     * For example, $item1 was saved with all_day = 1 and a timezone field UTC+02:00, but the dates within the database fields are translated to app timezone.
     *
     * When rendering the date information of $item1 we translate the date back to UTC+02:00 and render startDate - endDate (UTC+02:00)
     *
     * @return string the timezone this item was originally saved, note this is
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