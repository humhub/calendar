<?php

namespace humhub\modules\calendar\interfaces\fullcalendar;

interface FullCalendarEventIF
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
     * Update url used when dragging a calendar event within the calendar view.
     * This only needs to be implemented when [[isEditable()]] returns true.
     *
     * @return string
     */
    public function getUpdateUrl();

    /**
     * Whether or not this calendar event supports drag/drop and resizing in the calendar view and the current
     * user is allowed to update the vent within the calendar view.
     *
     * This should check `$this->content->canEdit()` in case of [[ContentActiveRecord]] based models.
     *
     * In order to support event updates in the calendar view the [[getUpdateUrl()]] should point to an
     * action responsible for updating the event.
     *
     * @return bool true if this entry can be updated in calendar view else false
     */
    public function isUpdatable();

    /**
     * Returns an url pointing to the view used in the calendar view, this can bei either a detail view or a
     * modal view.
     *
     * When returning a detail-view url [[getCalendarViewMode()]] should return 'redirect'.
     * When returning a modal-view url [[getCalendarViewMode()]] should return 'modal'.
     *
     * @return string
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
     * @return array
     */
    public function getFullCalendarOptions();
}