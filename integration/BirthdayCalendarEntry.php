<?php

namespace humhub\modules\calendar\integration;

use DateTime;
use humhub\modules\calendar\interfaces\event\CalendarTypeIF;
use humhub\modules\calendar\interfaces\fullcalendar\FullCalendarEventIF;
use humhub\widgets\Label;
use Yii;
use yii\base\Model;
use yii\helpers\Html;

class BirthdayCalendarEntry extends Model implements FullCalendarEventIF
{
    /**
     * @var BirthdayUserModel
     */
    public $model;

    /**
     * Returns an unique id for this event, which is used beside others in ICal exports.
     * When implementing [[EditableEventIF]] the uid will be assigned automatically when saving the event
     * unless the module itself did already assign a uid.
     *
     * @return string|null
     * @see EditableEventIF
     * @see https://www.kanzaki.com/docs/ical/uid.html
     */
    public function getUid()
    {
        return 'birthday' . $this->model->guid;
    }

    /**
     * Returns the [[CalendarTypeIF]] of this type.
     * @return CalendarTypeIF instance of the related calendar type
     */
    public function getEventType()
    {
        return new BirthdayCalendarType();
    }

    /**
     * Defines whether or not this event is an spans over an whole day.
     * Note all_day events should omit any timezone translations.
     *
     * @return bool
     */
    public function isAllDay()
    {
        return true;
    }

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
     * @throws \Exception
     * @see AbstractCalendarQuery::$startField
     */
    public function getStartDateTime()
    {
        return new DateTime($this->model->getAttribute('next_birthday'));
    }

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
     * @throws \Exception
     * @see AbstractCalendarQuery::$endField
     */
    public function getEndDateTime()
    {
        return $this->getStartDateTime();
    }

    /**
     * The timezone string this event was originally saved in, which does not have to be the same timezone [[getStartDateTime()]]
     * and [[getEndDateTime()]].
     *
     * @return string  The timezone string of this item.
     */
    public function getTimezone()
    {
        return null;
    }

    /**
     * The timezone string of the end date.
     * In case the start and end timezone is the same, this function can return null.
     *
     * @return string|null
     */
    public function getEndTimezone()
    {
        return null;
    }

    /**
     * Returns an url pointing to the detail view of this event. This function should not return an url to
     * a modal view.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->model->getUrl();
    }

    /**
     * Returns a hex color string e.g: '#44B5F6', which defines the color of this specific event.
     * When null is returned a default color of the related [[CalendarTypeIF]] is used.
     *
     * @return string|null hex color string e.g: '#44B5F6'
     */
    public function getColor()
    {
        return null;
    }

    /**
     * @return string a human readable title for this event
     */
    public function getTitle()
    {
        return Yii::t('CalendarModule.base', '{displayName} Birthday', ['displayName' => Html::encode($this->model->getDisplayName())]);
    }

    /**
     * @return string|null a location of this event
     */
    public function getLocation()
    {
        return null;
    }

    /**
     * @return string|null a description of this event
     */
    public function getDescription()
    {
        return null;
    }

    /**
     * (optional) A badge/label used in snippets
     *
     * @return Label|string|null
     * @throws \Exception
     */
    public function getBadge()
    {
        $type = $this->getEventType();
        return Label::asColor($this->getColor(), $type->getTitle())->icon($type->getIcon())->right();
    }

    /**
     * (optional) Additional configuration options
     * @return array
     */
    public function getCalendarOptions()
    {
        return [];
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
        return null;
    }

    /**
     * Optional sequence support see https://www.kanzaki.com/docs/ical/sequence.html
     *
     * This function should return 0 in case sequences is not supported.
     *
     * @return int
     */
    public function getSequence()
    {
        return 0;
    }

    /**
     * Whether or not this calendar event supports drag/drop and resizing in the calendar view and the current
     * user is allowed to update the vent within the calendar view.
     *
     * This should check `$this->content->canEdit()` in case of [[ContentActiveRecord]] based models.
     *
     * The default update action can be overwritten by setting the `updateUrl` fullcalendar option.
     *
     * In case this function just returns false, drag/drop and resize will be disabled for all events.
     *
     * Note: Currently the default update mechanism only works for ContentActiveRecord based models. In order to
     * implement calendar view updates for non content models a custom action url has to be provided as `updateUrl`
     * option. See [[FullCalendarController::actionUpdate]] as reference implementation.
     *
     * @return bool true if this entry can be updated in calendar view else false
     */
    public function isUpdatable()
    {
        return false;
    }

    /**
     * Used to update and persist the start and end datetime of this event. This function has to be implemented in case
     * `isUpdatable()` is supported and the default calendar view mechanism is used.
     *
     * When using the default update action the $start and $end dates are provided in system timezone.
     *
     * The implementation of this function is optional in case a custom update action is defined in the `updateUrl` option.
     * A custom action needs to manually translate the dates to system timezone in case of non all day events
     * and either call [[updateTime()]] manually or use another implementation (in which case the [[updateTime()]]
     * function should just return false.
     *
     * This function should implement all necessary validation. The default update action will automatically
     * validate `$event->isUpdatable()` in order to check if the current user is allowed to edit the event.
     *
     * In case the event could not be saved this function should return an error message string or false otherwise true.
     *
     * @param DateTime $start
     * @param DateTime $end
     * @return bool|string
     */
    public function updateTime(DateTime $start, DateTime $end)
    {
        return false;
    }

    /**
     * Returns an url pointing to the view used in the calendar view, this can either be a detail view or a
     * modal view.
     *
     * When returning a detail-view url [[getCalendarViewMode()]] should return 'redirect'.
     * When returning a modal-view url [[getCalendarViewMode()]] should return 'modal'.
     *
     * In case this function returns null [[CalendarEventIF:getUrl()]] will be used as fallback url in combination with
     * view mode redirect.
     *
     * @return string|null
     */
    public function getCalendarViewUrl()
    {
        return $this->getUrl();
    }

    /**
     * Defines how the calendar event is opened once a user clicks on it within the calendar view.
     *
     * Depending on the result of [[getCalendarViewUrl]] this function should return:
     *
     * - `modal` for modal based views
     * - `redirect` for full page detail views
     *
     * @return string view mode 'modal', 'redirect'
     */
    public function getCalendarViewMode()
    {
        return static::VIEW_MODE_REDIRECT;
    }

    /**
     * Add additional options supported by fullcalendar: https://fullcalendar.io/docs/event-object
     *
     * Additional non standard options:
     *
     *  - 'updateUrl': overwrite default update url
     *  - 'refreshAfterUpdate': will force the calendar to refresh all events after an update (drag/drop or resize) default false
     *
     * @return array
     */
    public function getFullCalendarOptions()
    {
        return [];
    }
}
