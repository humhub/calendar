<?php

namespace humhub\modules\calendar\interfaces\fullcalendar;

use DateTime;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;

interface FullCalendarEventIF extends CalendarEventIF
{
    /**
     * Used when the detail view should be opened within a modal
     */
    const VIEW_MODE_MODAL = 'modal';

    /**
     * Used when the detail view should be opened by redirect link
     */
    const VIEW_MODE_REDIRECT = 'redirect';

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
    public function isUpdatable();

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
     * @return boolean|string
     */
    public function updateTime(DateTime $start, DateTime $end);

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
    public function getCalendarViewUrl();

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
    public function getCalendarViewMode();

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
    public function getFullCalendarOptions();
}