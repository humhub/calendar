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

namespace humhub\modules\calendar\interfaces\event;


use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\widgets\Label;
use DateTimeInterface;

/**
 * This interface serves as the base interface for all types of calendar events.
 *
 * ## UID
 *
 * The uid of an event can be accessed by [[getUid()]]. The module related to the event type can either
 * manually assign a UID, or let the calendar assign a uid which requires the event model to implement
 * [[EditableEventIF]].
 *
 * ## Date ranges
 *
 * The most important interface functions are [[getStartDateTime()]] and [[getEndDateTime()]] which define the
 * time range of the event.
 *
 * In most cases this interface is implemented by [[ContentActiveRecord]] classes which ideally should use the
 * following database fields with the db date format `Y-m-d H:i:s`:
 *
 * - `start_datetime` as datetime field defining the start of the event
 * - `end_datetime` as datetime field defining the start of the event
 *
 * In case your class uses different field names or format, the field names have to be aligned in [[AbstractCalendarQuery::$startField]]
 * and [[AbstractCalendarQuery::$startField]] or [[AbstractCalendarQuery::$dateFormat]].
 *
 * > Note: Dates in HumHub are usually saved in system timezone and then translated to user timezone (for non all day events).
 * The [[getTimeZone()]] function returns the timezone the date was originally saved.
 *
 * ## All day events
 *
 * The following example shows the start and end datetime result of a single all day event:
 *
 * - [[getStartDateTime()]] - 2020-01-02 00:00:00
 * - [[getEndDateTime()]] - 2020-01-02 23:59:59
 *
 * > Note: There should be no timezone translation for all day events.
 *
 * ## Calendar View
 *
 * The following functions defines how the event is handled within the calendar:
 *
 * - [[getCalendarViewUrl()]] returns the url to a view which is accessed when the event is clicked in the calendar view
 * - [[getCalendarViewMode()]] defines how the view is opened (modal or redirect)
 * - [[isEditable()]] whether or not this event supports the drag/drop and resize feature of the calendar view
 * - [[getUpdateUrl()]] the url used when drag/drop or resize feature is used in calendar view
 *
 * @package humhub\modules\calendar\interfaces\event
 * @see AbstractCalendarQuery
 */
interface CalendarEventIF
{
    /**
     * Returns an unique id for this event, which is used beside others in ICal exports.
     * When implementing [[EditableEventIF]] the uid will be assigned automatically when saving the event
     * unless the module itself did already assign a uid.
     *
     * @return string|null
     * @see EditableEventIF
     * @see https://www.kanzaki.com/docs/ical/uid.html
     */
    public function getUid();

    /**
     * Returns the [[CalendarTypeIF]] of this type.
     * @return CalendarTypeIF instance of the related calendar type
     */
    public function getEventType();

    /**
     * Defines whether or not this event is an spans over an whole day.
     * Note all_day events should omit any timezone translations.
     *
     * @return boolean
     */
    public function isAllDay();

    /**
     * Returns the datetime this event starts.
     * In case of all day events timezone have to be omitted:
     *
     * ```
     * 09.01.2020 00:00:00
     * ```
     *
     * The timezone of this date does not has to be the same as [[getTimezone()]] as in the following example:
     *
     * ```
     * public function getStartDateTime()
     * {
     *    return new DateTime($this->start_datetime, CalendarUtils::getSystemTimeZone(false));
     * }
     * ```
     *
     * > Note: Ideally the underlying database field should have the name `start_datetime` otherwise the [[AbstractCalendarQuery::$startField]]
     * > needs to be overwritten.
     *
     * @return DateTime start datetime
     * @see AbstractCalendarQuery::$startField
     */
    public function getStartDateTime();

    /**
     * Returns the datetime this event ends.
     * In case of all day events this should be the moment right before the next day no matter the timezone:
     *
     * ```
     * 09.01.2020 23:59:59
     * ```
     *
     * The timezone of this date does not has to be the same as [[getTimezone()]] as in the following example:
     *
     * ```
     * public function getEndDateTime()
     * {
     *    return new DateTime($this->end_datetime, CalendarUtils::getSystemTimeZone(false));
     * }
     * ```
     *
     * > Note: Ideally the underlying database field should have the name `end_datetime`, otherwise the [[AbstractCalendarQuery::$endField]]
     * > needs to be overwritten.
     *
     * @return DateTime start datetime
     * @see AbstractCalendarQuery::$endField
     */
    public function getEndDateTime();

    /**
     * The timezone string this event was originally saved in, which does not have to be the same timezone [[getStartDateTime()]]
     * and [[getEndDateTime()]].
     *
     * @return string  The timezone string of this item.
     */
    public function getTimezone();

    /**
     * The timezone string of the end date.
     * In case the start and end timezone is the same, this function can return null.
     *
     * @return string|null
     */
    public function getEndTimezone();

    /**
     * Returns an url pointing to the detail view of this event. This function should not return an url to
     * a modal view.
     *
     * @return string
     */
    public function getUrl();

    /**
     * Returns a hex color string e.g: '#44B5F6', which defines the color of this specific event.
     * When null is returned a default color of the related [[CalendarTypeIF]] is used.
     *
     * @return string|null hex color string e.g: '#44B5F6'
     */
    public function getColor();

    /**
     * @return string a human readable title for this event
     */
    public function getTitle();

    /**
     * @return string|null a location of this event
     */
    public function getLocation();

    /**
     * @return string|null a description of this event
     */
    public function getDescription();

    /**
     * (optional) A badge/label used in snippets
     *
     * @return Label|string|null
     */
    public function getBadge();

    /**
     * (optional) Additional configuration options
     * @return array
     */
    public function getCalendarOptions();

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
    public function getLastModified();

    /**
     * Optional sequence support see https://www.kanzaki.com/docs/ical/sequence.html
     *
     * This function should return 0 in case sequences is not supported.
     *
     * @return int
     */
    public function getSequence();
}