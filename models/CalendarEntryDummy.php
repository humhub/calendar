<?php

namespace humhub\modules\calendar\models;

use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\event\CalendarTypeIF;
use humhub\modules\calendar\interfaces\event\EditableEventIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceQueryIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\widgets\Label;
use yii\base\Model;

/**
 * This is the model class for table "calendar_entry".
 *
 * The followings are the available columns in table 'calendar_entry':
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $start_datetime
 * @property string $end_datetime
 * @property int $all_day
 * @property int $participation_mode
 * @property string $color
 * @property string $uid
 * @property int $allow_decline
 * @property int $allow_maybe
 * @property string $participant_info
 * @property int closed
 * @property int max_participants
 * @property string rrule
 * @property string recurrence_id
 * @property int parent_event_id
 * @property string exdate
 * @property string sequence
 * @property CalendarEntryParticipant[] participantEntries
 * @property string $time_zone The timeZone this entry was saved, note the dates itself are always saved in app timeZone
 * @property string $location
 * @property-read bool $recurring
 * @property-read bool $reminder
 */
class CalendarEntryDummy extends Model implements CalendarEventIF, RecurrentEventIF
{
    /**
     * @var DateTime
     */
    public $start;

    /**
     * @var DateTime
     */
    public $end;

    /**
     * @var bool
     */
    public $isAllDay = true;

    /**
     * @var string
     */
    public $rrule;

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
        return 'DUMMY';
    }

    /**
     * Returns the [[CalendarTypeIF]] of this type.
     * @return CalendarTypeIF instance of the related calendar type
     */
    public function getEventType()
    {
        return null;
    }

    /**
     * Defines whether or not this event is an spans over an whole day.
     * Note all_day events should omit any timezone translations.
     *
     * @return bool
     */
    public function isAllDay()
    {
        return $this->isAllDay;
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
     * @see AbstractCalendarQuery::$startField
     */
    public function getStartDateTime()
    {
        return $this->start;
    }

    /**
     * Returns the datetime this event ends.
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
    public function getEndDateTime()
    {
        return $this->end;
    }

    /**
     * The timezone string this event was originally saved in, which does not have to be the same timezone [[getStartDateTime()]]
     * and [[getEndDateTime()]].
     *
     * @return string  The timezone string of this item.
     */
    public function getTimezone()
    {
        return CalendarUtils::getSystemTimeZone(true);
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
        return null;
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
        return 'Dummy';
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
        return 'Dummy';
    }

    /**
     * (optional) A badge/label used in snippets
     *
     * @return Label|string|null
     */
    public function getBadge()
    {
        return null;
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
     * Sets the uid of this event. This only have to be implemented if the module does not generate own UIDs.
     * If not actually implemented (empty) a new UID is created every time this event is exported.
     *
     * @param $uid
     * @return mixed
     */
    public function setUid($uid)
    {
        // TODO: Implement setUid() method.
    }

    /**
     * Sets the event revision sequence, this is optional and can be implemented as empty function if not supported.
     *
     * The calendar interface increments the sequence automatically when an EditableEventIF content entry is saved
     * or when using the [[RecurrenceFormModel]].
     *
     * @param $sequence
     * @return mixed
     */
    public function setSequence($sequence)
    {
        // TODO: Implement setSequence() method.
    }

    /**
     * Should update all data used by the event interface setter.
     *
     * @return bool|int
     */
    public function saveEvent()
    {
        // TODO: Implement saveEvent() method.
    }

    /**
     * @return int the id of this event
     */
    public function getId()
    {
        // TODO: Implement getId() method.
    }

    public function getRecurring(): bool
    {
        return !empty($this->rrule);
    }

    public function isRecurringEnabled(): bool
    {
        return $this->recurring;
    }

    /**
     * @return string the rrule string
     */
    public function getRrule()
    {
        return $this->rrule;
    }

    /**
     * Sets the $rrule string of this event.
     *
     * @param string|null $rrule
     */
    public function setRrule($rrule)
    {
        $this->rrule = $rrule;
    }

    /**
     * Returns the id of the recurrent root event.
     *
     * > Note: The root id should not be set manually
     *
     * @return int the id of the root event
     */
    public function getRecurrenceRootId()
    {
        // TODO: Implement getRecurrenceRootId() method.
    }

    /**
     * Sets the id of the recurrence root event.
     *
     * > Note: The root id should not be set manually.
     *
     * @param int $rootId sets the root id of a recurrence instance
     */
    public function setRecurrenceRootId($rootId)
    {
        // TODO: Implement setRecurrenceRootId() method.
    }

    /**
     * Returns the recurrence id of this event.
     *
     * > Note: The recurrence id should not be set manually
     *
     * @return string|null
     */
    public function getRecurrenceId()
    {
        // TODO: Implement getRecurrenceId() method.
    }

    /**
     * Sets the recurrence id.
     *
     * > Note: The recurrence id should not be set manually
     *
     * @param $recurrenceId
     * @return mixed
     */
    public function setRecurrenceId($recurrenceId)
    {
        // TODO: Implement setRecurrenceId() method.
    }

    public function getReminder(): bool
    {
        return false;
    }

    /**
     * Returns a comma seperated list of recurrence ids, which are used to exclude specific dates from the recurring
     * root event.
     *
     * > Note: Exdates are set automatically in the beforeDelete handler of ContentActiveRecord based events.
     *
     * @return string|null
     */
    public function getExdate()
    {
        // TODO: Implement getExdate() method.
    }

    /**
     * Sets the exdate field, which are used to exclude specific dates from the recurring root event.
     *
     * In order to create an exdate string the [[RecurrenceHelper::addExdates()]] can be used.
     *
     * > Note: Exdates are set automatically in the beforeDelete handler of ContentActiveRecord based events.
     *
     * @param $exdateStr
     * @return mixed
     * @see RecurrenceHelper::addExdates()
     */
    public function setExdate($exdateStr)
    {
        // TODO: Implement setExdate() method.
    }

    /**
     * This function is called while expanding a recurrent root event.
     * Usually the implementation of this event sets the `start_datetime` and `end_datetime` and doing some other
     * initialization tasks. Note you won't
     *
     * Example:
     *
     * ```php
     * public function createRecurrence($start, $end)
     * {
     *   $instance = new self($this->content->container, $this->content->visibility);
     *   $instance->start_datetime = $start;
     *   $instance->end_datetime = $end;
     *   $instance->syncEventData($this);
     *   return $instance;
     * }
     * ```
     *
     * @param string $start start of the event in db format
     * @param string $end end of the event in db format
     * @return static
     */
    public function createRecurrence($start, $end)
    {
        // TODO: Implement createRecurrence() method.
    }

    /**
     * This function is called in order to synchronize a recurrent instance with a given root event.
     * An implementation of this function should copy all relevant data as for example description and title
     * from the root event.
     *
     * This function is called on recurrence instances after they were expanded. In this case the $original parameter is
     * null and the this instance is not persisted.
     *
     * Furthermore, this function is called on existing recurrent events once a root event is saved. In this case the
     * $original parameter points to the original root before the update. The function may skip some synchronizations in
     * this case, for example if the recurrent instance description was changed before the root edit:
     *
     * ```
     * public function syncEventData($root, $original = null) {
     *     // Only change title if we did not overwrite the original title already
     *     if(!$original || $original->title === $this->title) {
     *         $this->title = $root->title;
     *     }
     * }
     * ```
     *
     *
     * This function is called by the recurrent interface once when expanding an recurrent instance and when editing
     * the root event. This function may behave differently when the $create parameter is set
     *
     * This function should synchronize the data of this event with the given $root event, by copying all data and
     * content as title description texts and additional model data.
     *
     * Here are examples of data which should be copied:
     *
     * - content owner [[Content::$created_by]]
     * - content visibility [[Content::$visibility]]
     * - title
     * - description
     * - timezone
     * - all_day
     *
     * The following data is copied by default and therefore does not have to be copied manually:
     *
     * - uid
     * - recurrence root id
     * - rrule
     *
     * The following data should not be touched:
     *
     *  - uid
     *  - start_datetime
     *  - end_datetime
     *  - recurrence_id
     *
     * @param $root static
     * @param $create
     * @return mixed
     */
    public function syncEventData($root, $original = null)
    {
        // TODO: Implement syncEventData() method.
    }

    /**
     * @return RecurrenceQueryIF
     */
    public function getRecurrenceQuery()
    {
        // TODO: Implement getRecurrenceQuery() method.
    }

    /**
     * Should delete the event. This is required in order to delete recurrent event instances.
     */
    public function delete()
    {
        // TODO: Implement delete() method.
    }
}
