<?php


namespace humhub\modules\calendar\interfaces\event;

/**
 * A CalendarTypeIF is used to provide some metadata for a type of event.
 * Calendar types are besides others used within the calendar configuration and can be disabled on global or space level.
 *
 * If a calendar type is disabled exported events won't show up in the calendar.
 *
 * @package humhub\modules\calendar\interfaces
 */
interface CalendarTypeIF
{
    /**
     * Returns a unique key of this type of event.
     * The event type key is besides others used in the autogeneration of uids and should therefore not use any special
     * characters or spaces.
     *
     * @return string an unique event type key.
     */
    public function getKey();

    /**
     * @return string a translated title for this kind of event
     */
    public function getTitle();

    /**
     * @return string a translated description of this type of event
     */
    public function getDescription();

    /**
     * @return string an optional default color (e.g. #ffffff) used for this kind of events which can be overwritten on global or space level.
     */
    public function getDefaultColor();

    /**
     * @return string an optional icon string
     */
    public function getIcon();
}