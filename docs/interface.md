# Calendar interface v1.0

This guide describes how to implement the calendar interface in order to inject events into the calendar module.

All interface classes reside within the `interface` directory of the calendar module. Calendar interface implementations
should reside within the  `integration/calendar` directory of your module.

> Note: Calendar v1.0 switched from an array type interface to real class level interfaces. The old array type interface is still
>supported but deprecated.

## Calendar item types

A calendar item type is used to provide some meta data of your custom event type as for example:

 - `title`: A translatable title
 - `description`: Short translatable description of your item type
 - `default color` (optional): A default color used for this item type, which can be overwritten in the calendar module config
 - `icon` (optional): Icon related to this event type e.g. `fa-calendar`
 
Add your own custom calendar item type by implementing the `humhub\modules\calendar\interfaces\CalendarTypeIF` as follows:

**CustomItemType.php:**

```php
use humhub\modules\calendar\interfaces\CalendarTypeIF;

class CustomItemType implements CalendarTypeIF
{
    const ITEM_TYPE = 'customEvent';

    public function getKey()
    {
        static::ITEM_TYPE;
    }

    public function getDefaultColor()
    {
        return '#ffffff';
    }

    public function getTitle()
    {
        return Yii::t('MymoduleModule.integration', 'CustomEvent');
    }

    public function getDescription()
    {
        return Yii::t('MymoduleModule.integration', 'A custom calendar event');
    }

    public function getIcon()
    {
        return 'fa-calendar-o';
    }
}
```

Configure an event listener for the `getItemTypes` of `humhub\modules\calendar\interfaces\CalendarService`:

**config.php**:

```php
return [
    'id' => 'mymodule',
    'class' => 'mymodule\Module',
    'namespace' => 'mymodule',
    'events' => [
        //...
        ['class' => 'humhub\modules\calendar\interfaces\CalendarService', 'event' => 'getItemTypes', 'callback' => ['mymodule\Events', 'onGetCalendarItemTypes']],
    ],
];
```

> Note: Define the class name as string to prevent a strict dependency to the calendar module. 

**Event.php:**

```php
public static function onGetCalendarItemTypes(CalendarItemTypesEvent $event)
{
    $contentContainer = $event->contentContainer;

    if(!$contentContainer || $contentContainer->isModuleEnabled('mymodule')) {
        $event->addType(CustomItemType::ITEM_TYPE, new CustomItemType());
    }
}
```

Your custom item type should now be listed within the `Other calendars` section of your global and space calendar module settings (in case the module is enabled).

> Note: Don't forget to check if your module is enabled on the given `$event->contentContainer`. If no `contentContainer` is
given it's meant to be a global search for all available calendar item types.

## Calendar Events Model

Custom event models have to implement the `humhub\modules\calendar\interfaces\CalendarEventIF`.
In most cases you'll want to implement an `ActiveRecord` based event model, or even better a `ContentActiveRecord` based
model. The following database fields should be used for your event model:

 - `uid`: An event [uid](https://www.kanzaki.com/docs/ical/uid.html) id which will be assigned automatically by the calendar interface in case `EditableEventIF` is implemented.
 - `start_datetime`: the start date time
 - `end_datetime`: end date time

> Note: In case you want to keep the dependency of your module to the calendar module optional, you should not directly
>implement the `CalendarEventIF` on the model class and instead implement a integration adapter class within your `integration/calendar`
>directory.

### CalendarEventIF implementation
 
The following example shows a calendar integration by means of a `ContentActiveRecord`, with optional dependency to the calendar module.
 
First implement your custom `ContentActiveRecord` class:

**mymodule/models/CustomEvent.php**:

```php
namespace mymodule/models;

class CustomEvent extends ConentActiveRecord {
    // Model logic of your module
}
```

Then implement the integration adapter class:

**mymodule/integration/calendar/CustomCalendarEvent.php**:

```
namespace mymodule/integration/calendar;

class CustomCalendarEvent extends CustomEvent implements CalendarEventIF {
  // Implement all missing CalendarEventIF functions not alrady defined in your base model class

 public static function find()
 {
    return new ActiveQueryContent(static::class);
 }
    
  public static function getObjectModel() {
    return CustomEvent::class;
  }
}
```

> Note: the `getObjectModel()` needs to be overwritten in order to force the content relation to the original `CustomEvent` class
in the content table instead of `CustomCalendarEvent`.

> Note: the `find()` function needs to be overwritten in order to stay compatible with HumHub version < 1.4, if your modules min-version is
>= 1.4 you can omit this.

Here is a short description of the interfac functions:

 - `getUid()`: The event uid as described above, when implementing `EditableEventIF`, this uid will be assigned automatically if no custom uid was assigned.
 - `getType()`: returns an instance of the related calendar type
 - `isAllDay()`: weather or not this event is an all day event
 - `getStartDateTime()`: start DateTime object of this event
 - `getEndDateTime()`: end DateTime object of this event
 - `getTimezone()`: string the timezone string of this event
 - `getEndTimezone()`: can be used in case the end date timezone differs from the start timezone, otherwise can be null
 - `getUrl()`: An url to the detail-view of this event, this will be used in the upcoming event snippet and in the calendar view
 - `getTitle()`: The event title
 - `getDescription()`: The event description
 - `getLastModified()`: The last modified DateTime used for ICal export e.g. `new DateTime($this->content->updated_at);`
 - `getColor()`: (optional) A color used within the calendar view and sidebar snippet, if null is returned the default color will be used
 - `getSequence()`: (optional) The event [revision sequence](https://www.kanzaki.com/docs/ical/sequence.html) 
 - `getLocation()`: (optional) an event location string
 - `getBadge()`: (optional) a `humhub\widgets\Label` or string used in the sidebar snippet
 - `getCalendarOptions()`: (optional) additional event configuration

> **Optional** functions can return null if not supported.

### AbstractCalendarQuery implementation

The calendar module will trigger the `findItems` event of `humhub\modules\calendar\interfaces\CalendarService` to fetch
all events from external modules. The event may contain different filters and the search range. In order to ease the implementation
of filtering your events, you should extend the `humhub\modules\calendar\interfaces\event\AbstractCalendarQuery` class.

The following example shows the most basic implementation of a custom event query:

**mymodule/integration/calendar/CustomCalendarEventQuery**:

```php
class CustomCalendarEventQuery extends AbstractCalendarQuery
{
    protected static $recordClass = CustomCalendarEvent::class;
}
```

The previous example implies that our `CustomCalendarEvent` model uses the default database fields for `start_datetime` and `end_datetime`
and the default database datetime format `Y-m-d H:i:s`.

The `AbstractCalendarQuery:dateQueryType` is used to define the behaviour of the date query, by setting one of the following values:

- `AbstractCalendarQuery:DATE_QUERY_TYPE_TIME` (default): Will assume all dates are timezone relevant and the default date format is `Y-m-d H:i:s`.
- `AbstractCalendarQuery:DATE_QUERY_TYPE_DATE`: Will assume all dates are all day events without timezone translations the default date format is `Y-m-d`.
- `AbstractCalendarQuery:DATE_QUERY_TYPE_MIXED`: Should be used in case all day events and time relevant events are mixed, the query will only consider timezone
offset differences between user and the system when an `all_day` database flag is not set.
 
Beside the `dateQueryType` the following fields can be overwritten in order to change the default behavior:

- `recordClass`: Required in order to set your `ActiveRecord` class used for the query
- `allDayField`: Defines the database field name of your models all day flag. This is only required when using `DATE_QUERY_TYPE_MIXED` (default is `all_day`) 
- `startField`: The name of your start date field (default `start_datetime`)
- `endField`: The name of your end date field (default `end_datetime`)
- `dateFormat`: The date format of start and end fields see above
 
In case your model extends `ContentActiveRecord` the query class provides a default implementation for the following filter:
 
 - `filterDashboard()`: this filter is used for the dashboard upcoming events snippets, by default this filter will make use of the `USER_RELATED_SCOPE_SPACE` and `USER_RELATED_SCOPE_OWN_PROFILE`
 - `filterGuests()`: used for guest users which are not able to use other filters
 - `filterUserRelated()`: used for user related queries e.g: 'Only content from following spaces' (see `ActiveQueryContent::userRelated`)
 - `filterContentContainer()`: used to filter content of a specific ContentContainer (Space/User)
 - `filterReadable()`: only include content readable by the  current user
 - `filterMine()`: only include items created by me
 - `setupDateCriteria()`: responsible for the date interval filter
 
Some filter can be implemented manually if supported by your model:
 
 - `filterIsParticipant()`: in case the item type supports an own participation logic, this filter is used to only include items
 in which the current logged in user participates (optional)
  
In case a filter is not supported, the respective filter function should throw a `FilterNotSupportedException`, which by default is the case for
all filters except the date criteria filter when a non `ContentActiveRecord` is used as base model.
 
> Note: Guest users are not able to use other filters than the `filterGuests`

The following example shows the implementation of a more complex AbstractCalendarQuery with custom start and end date field name
, custom date format and custom participation filter. 

**MeetingCalendarQuery example:**

```php
class MeetingCalendarQuery extends AbstractCalendarQuery
{
   
    protected static $recordClass = Meeting::class;

    public $startField = 'start_date';
    
    public $endField = 'end_date';

    /**
     * @inheritdoc
     */
    public function filterIsParticipant()
    {
        $this->_query->leftJoin('meeting_participant', 'meeting.id=meeting_participant.meeting_id AND meeting_participant.user_id=:userId', [':userId' => $this->_user->id]);
        $this->_query->andWhere('meeting_participant.id IS NOT NULL');
    }
}
```

### Inject calendar entries

In order to inject our events to the calendar we need to listen to the `findItems` event of `humhub\modules\calendar\interfaces\CalendarService` as follows:

**config.php**:

```php
return [
    'events' => [
        ['class' => 'humhub\modules\calendar\interfaces\CalendarService', 'event' => 'findItems', 'callback' => ['mymodule\Events', 'onFindCalendarItems']],
    ],
];
``` 

**Event.php:**

```php
public static function onFindCalendarItems(CalendarItemsEvent $event)
{
    $contentContainer = $event->contentContainer;

    if(!$contentContainer || $contentContainer->isModuleEnabled('mymodule')) {
        $event->addItems(static::ITEM_TYPE_KEY, CustomCalendarEventQuery::findForEvent($event));
    }
}
```

### Implementation of EditableEventIF

The `humhub\modules\calendar\interfaces\event\EditableEventIF` extends the `CalendarEventIF` and can be implemented in order to support auto `uid` generation 
when saving or fetching a model.

The interface extends the base calendar interface with the following functions:

- `setUid($uid)`: Set the uid of this event, in case no manual uid generation was done
- `save()`: Should persist all data set by this or sub interfaces.
- `setSequence($sequence)`: (optional) Should set the sequence counter field if supported by your event type

**Example:**

```
class CustomCalendarEvent extends CustomEvent implements EditableEventIF {
  // Implementation of other CalendarEventIF functions...
  
  public function setUid($uid) 
  {
    $this->uid = $uid;
  }

  public function setSequence($sequence) 
  {
    $this-sequence = $sequence;
  }
  
  public function saveEvent()
  {
   return $this->save();
  }

  public static function find()
  {
    return new ActiveQueryContent(static::class);
  }
}
```

### Implementation of FullCalendarEventIF

The `humhub\modules\calendar\interfaces\fullcalendar\FullCalendarEventIF` can be used to change the event
behavior in the calendar view or set additional [Fullcalendar options](https://fullcalendar.io/docs/event-object)

The following functions are available to change the event behavior:

- `isUpdatable()`: enables the drag/drop and resize feature of fullcalendar for this event. Here you should make sure the current logged
in user is allowed to edit the underlying model e.g. `$this->content->canEdit()`
- `updateTime()`: should update and persist the start and end DateTime of this event.
- `getCalendarViewUrl()`: here you can overwrite the url returned by `getUrl()`, usually used to provide a modal view instead
of a detail view. If null is returned the `getUrl()` is used and `redirect` view mode is used.
- `getCalendarViewMode()`: defines the way the event is opened after being clicked in the calendar view.
  - `modal`: should be used for modal based views
  - `redirect`: is the default and should be used for a detail view opened as full page
- `getFullCalendarOptions()`: see [Fullcalendar options](https://fullcalendar.io/docs/event-model)

In case you want to skip the default drag/drop and resize update mechanism and implement a own one, you can
set a `updateUrl` option within `getFullCalendarOptions()`.

In case you need to refresh the whole calendar view after drag/drop and resize you can set the `refreshAfterUpdate`
option within `getFullCalendarOptions()`. This is may required if updating your event affects other events as well.

**Example:**

```
class CustomCalendarEvent extends CustomEvent implements FullCalendarEventIF {
   // Implementation of other CalendarEventIF functions...
   
  public function isUpdatable() 
  {
    return $this->content->canEdit();
  }
  
  public function updateTime(DateTime $start, DateTime $end)
  {
    $this->start_datetime = CalendarUtils::toDBDateFormat($start);
    $this->end_datetime = CalendarUtils::toDBDateFormat($end);
    return $this->save();
  }
  
  public function getCalendarViewUrl()
  {
    return $this->conent->container->createUrl('/mymodule/calendar/view-modal', ['id' => $this-id]);
  }
  
  public function getCalendarViewMode()
  {
    return static::VIEW_MODE_MODAL;
  }

 public function getFullCalendarOptions()
 {
    return [
        'rendering' => 'background';
    ]
 }
}
```

### Recurrent events

A recurrent event consist of a root event and multiple recurrent instances. The root event serves as
template for all recurrent instances and is not part of a calendar query result itself. 
The first instance of a recurring event has the same start/end date as the root event unless it has been updated.

The recurrent event interface provided by the calendar module supports:

- The creation of `rrules` by means of the `humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel` 
and `humhub\modules\calendar\interfaces\recurrence\widgets\RecurrenceFormWidget`
- The filtering and expansion of recurring events by means of `humhub\modules\calendar\interfaces\recurrenc\AbstractRecurrenceQuery`
- Editing of recurrent events either by
  - Editing all events
  - Splitting an recurrent event into two seperate recurrent events
  - Edit single instances which serve as exceptional events
  - Deleting recurrence instances with automatic `exdate` management
  - Deleting a recurrence root with all recurrence instances

### Implementation of RecurrentEventIF
The `humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF` can be used in order to support recurrent events.
Your recurrent event model needs to support the following fields:

- `id` the event id
- `sequnece` the event id
- `start_datetime` as datetime field defining the start of the event
- `end_datetime` as datetime field defining the start of the event
- `rrule` a [rrule](https://www.kanzaki.com/docs/ical/rrule.html) string
- `exdate` a string field containing comma seperated [exdates](https://www.kanzaki.com/docs/ical/exdate.html)
- `parent_event_id` the id of the recurring root event

A recurring event and instances have to follow the following urles:

- `rrule` is set on both recurrent instances and root events in order to detect it as recurrent
- `paren_event_id` is not set on non recurrent events and not set on the root event itself
- `exdate` is only set on the root event

The interface requires the following additional functions:

- `getId()`: returns the id of your model
- `getRecurrenceRootId()`: returns the id of the root event
- `getRrule()`: returns the [rrule](https://www.kanzaki.com/docs/ical/rrule.html) in case the event is recurrent
- `setRrule()`: used to set the [rrule](https://www.kanzaki.com/docs/ical/rrule.html) of an event
- `getRecurrenceId()`: returns the [recurrence id](https://www.kanzaki.com/docs/ical/recurrenceId.html)  of this event
- `setRecurrenceId()`: sets the [recurrence id](https://www.kanzaki.com/docs/ical/recurrenceId.html) of this event
- `getExdate()`: returns the  [https://www.kanzaki.com/docs/ical/exdate.html](https://www.kanzaki.com/docs/ical/exdate.html) string of a root event
- `setExdate()`: sets the  [https://www.kanzaki.com/docs/ical/exdate.html](https://www.kanzaki.com/docs/ical/exdate.html) string of a root event
- `createRecurrence()`: is used to create an event instance for a given start and end (without persisting it!)
- `syncEventData()`: should copy all necessary data of a given root event into this instance
- `getRecurrenceQuery()`: should return an instance of your `AbstractRecurrenceQuery`
- `delete()`: deletes a model from the database

**Example**

```
class CustomCalendarEvent extends CustomEvent implements RecurrentEventIF {
  
 private $query;
 
 public function init()
 {
     parent::init();

     $this->query = new CustomCalendarEventRecurrenceQuery(['event' => $this]);
 }
 
  // Implementation of other CalendarEventIF functions...
 
 public function getId()
 {
    return $this->id;
 }
 
 public function getRecurrenceRootId()
 {
    return $this->parent_event_id;
 }
 
 public function getRrule()
 {
    return $this->rrule;
 }
 
 public function setRrule($rrule)
 {
    $this->rrule = $rrule;
 }
 
 public function getRecurrenceId()
 {
    return $this->recurrence_id;
 }
 
 public function setRecurrenceId($recurrenceId)
 {
   $this->recurrence_id = "recurrence_id;
 }
 
 public function getExdate()
 {
    return $this->exdate;
 }
 
 public function setExdate($exdate)
 {
    $this->exdate = $exdate;
 }
 
 public function createRecurrence($start, $end)
 {
     $instance = new self($this->content->container, $this->content->visibility);
     $instance->start_datetime = $start;
     $instance->end_datetime = $end;

     // Turn off notifications and wall entry creation
     $instance->silentContentCreation = true;
     $instance->content->stream_channel = null;

     return $instance;
 }
 
 public function syncEventData($root, $original = null)
 {
     $this->content->created_by = $root->content->created_by;
     $this->content->visibility = $root->content->visibility;

     // Only align description if we did not already overwrite it for this event
     if (!$original || empty($this->description) || $original->description === $this->description) {
         $this->description = $root->description;
     }

     if (!$original || empty($this->participant_info) || $original->participant_info === $this->participant_info) {
         $this->participant_info = $root->participant_info;
     }

     $this->title = $root->title;
     $this->time_zone = $root->time_zone;
     $this->all_day = $root->all_day;
 }
 
 public function getRecurrenceQuery()
 {
    return $this->query;
 }
}
```

> Note: `delete()` is already implemented by `ActiveRecord`.

### Implementation of AbstractRecurrenceQuery

In case of a recurrent event model your query class need to extend
`humhub\modules\calendar\interfaces\recurrence\AbstractRecurrenceQuery` instead of the `AbstractCalendarQuery` which
allows you to overwrite the following fields in addition to the fields defined in `AbstractCalendarQuery`.

- `idField`: the field name of your record `id` field (default `id`)
- `rruleField`: the field name of your `rrule` field (default `rrule`)
- `sequenceField`: the field name of your `sequence` field (default `sequence`)
- `recurrenceIdField`:  the field name of your `recurrence_id` field (default `recurrence_id`)

When following the database field naming recommendation a recurrence query class can look like:

```
class CustomCalendarEventRecurrenceQuery extends AbstractRecurrenceQuery
{
    public static $recordClass = CustomCalendarEvent::class;
}
```

### CalendarEventReminderIF

The `humhub\modules\calendar\interfaces\reminder\CalendarEventReminderIF` is used to support setting
reminders for an event. This interface is currently only supported by `ContentActiveRecord` based events and
extends the `CalendarEventIF` by:

 - `getContentRecord()`: should return the related `Content` instance
 - `getReminderUserQuery()`: should return an `ActiveQueryUser` filtering users to receive the reminder
 
```
class CustomCalendarEvent extends CustomEvent implements CalendarEventReminderIF {
 // Implementation of other CalendarEventIF functions...
  public function getContentRecord()
  {
    return $this->content;
  }
  
  public function getReminderUserQuery() {
    return $this->findParticipantUsers();
  }
}
```

When implementing an optional dependency to the calendar module as in the previous examples your base
model `CustomEvent` needs to implement a `getCalendarEvent()` function which returns a `CustomCalendarEvent`.
This is required to determine a `CalendarEventIF` from a given `Content` instance.

```
public function getCalendarEvent()
{
    return new CustomCalendarEvent($this->attributes);
}
```
