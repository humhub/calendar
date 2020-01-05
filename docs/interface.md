# Calendar interface v1.0

This guide describes how to facilitate the calendar interface in your own custom module in order to inject own
event types into the calendar.

All interface files reside within the `interface` directory of the calendar module. Calendar interface implementations
should reside within the  `integration/calendar` directory of your custom module.

> Note: Calendar v1.0 switched from an array type interface to real class level interfaces. The old array type interface is still
>supported but deprecated.

## Calendar item types

A calendar item type is used to provide some meta data of your custom event type as for example:

 - `title`: A translatable title
 - `description`: Short translatable description of your item type
 - `default color` (optional): A default color used for this item type, which can be overwritten in the calendar module config
 - `icon` (optional): Icon related to this event type e.g. `fa-calendar`
 
Add your own custom calendar item type by implementing the `humhub\modules\calendar\interfaces\CalendarTypeIF`:

**MeetingItemType.php:**

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

Then configure a event listener for the `getItemTypes` of `humhub\modules\calendar\interfaces\CalendarService`:

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

## Calendar Events

Custom calendar event models have to implement the `humhub\modules\calendar\interfaces\CalendarEventIF`.
In case you are implementing a `ActiveRecord` based event model, your record table should ideally contain the following fields:

 - `uid`: A unique id, the uid can also be created automatically by the calendar interface when using the `AbstractCalendarQuery`
 - `start_datetime`: start date time (usually saved in system timezone for non all day events)
 - `end_datetime`: end date time (usually saved in system timezone for non all day events)

> Note: In most cases want to keep the dependency of your module to the calendar module optional. Therefore you should not directly
>implement the `CalendarEventIF` on the model class and instead implement a integration class within your `integration/calendar`
>directory as in the following example.

### CalendarEventIF implementation
 
The following example shows a calendar integration by means of a `ContentActiveRecord`.
 
First implement your custom `ContentActiveRecord` class:

**mymodule/models/CustomEvent.php**:

```php
namespace mymodule/models;

class CustomEvent extends ConentActiveRecord {
    // Model logic of your module
}
```

Then implement the integration class:

**mymodule/integration/calendar/CustomCalendarEvent.php**:

```
namespace mymodule/integration/calendar;

class CustomCalendarEvent extends CustomEvent implements CalendarEventIF {
  // Implement all missing CalendarEventIF functions not alrady defined in your base model class

  public static function getObjectModel() {
    return CustomEvent::class;
  }
}
```

Your integration needs to implement the following functions:

 - `getUid()`: returns a uid for this event. This is besides others used for ICal exports and can be automatically be created when using the `AbstractCalendarQuery` with active `$autoAssignUid`
 - `getType()`: returns an instance of the related calendar type
 - `isAllDay()`: weather or not this event is an all day event
 - `getStartDateTime()`: start datetime object of this calendar item, ideally with timezone information
 - `getEndDateTime()`: end datetime object of this calendar item, ideally with timezone information
 - `getTimezone()`: string the timezone string
 - `getUrl()`: An url to the detail view of this event, e.g. used in the sidebar snippet
 - `getCalendarViewUrl()`: an url to the view used when clicking on a calendar entry in the calendar view, used in combination with `getCalendarViewMode()`
 - `getCalendarViewMode()`: One of the following view modes: `modal`, `blank`, `redirect`. Used when clicking on a calendar entry within the calendar view.
 - `getUpdateUrl()`: An action url used to update the start/end of the event when dragging the event within the calendar only required when `isEditable()` return true
 - `isEditable()`: Weather or not the calendar entry can be directly edited within the calendar view by drag/drop, should make use of `$model->content->canEdit()` when working with ContentActiveRecords
 - `getColor()`: A color used within the calendar view and sidebar snippet
 - `getTitle()`: A event title, e.g. used for ICal export and the sidebar snippet
 - `getDescription()`: A event description e.g. used for ICal export and the sidebar snippet
 - `getLocation()`: (optional) a event location string
 - `getBadge()`: (optional) a label used in the sidebar snippet
 - `getIcon()`: (optional) a icon used in the sidebar snippet e.g. 'fa-calendar'
 - `getCalendarOptions()`: (optional) additional configuration for future use
 
### AbstractCalendarQuery implementation

You should implement a custom `AbstractCalendarQuery` when using `ActiveRecord` based calendar models. 
The `AbstractCalendarQuery` will be responsible for querying your events and automatically supports some calendar filters
by default. A very simple `AbstractCalendarQuery` is shown in the following example:

**mymodule/integration/calendar/CustomCalendarEventQuery**:

```php
class CustomCalendarEventQuery extends AbstractCalendarQuery
{
    protected static $recordClass = CustomCalendarEvent::class;
}
```

The previous example implies that our CustomCalendarEvent model uses the default database fields for `start_datetime` and `end_datetime`
and the default date time format. For more complex scenarios, please refer to:
 
 Subclasses of AbstractCalendarQuery may overwrite:
 
 - `recordClass`: a `ActiveRecord` class string used for initializing the query.
 - `startField`: the name of the database field for the start date
 - `endField`: the name of the database field for the end date, if there is no explicit end field use the start field
 - `dateFormat`: the database date format of your date fields 
 - `rruleField`, `parentEventIdField`: used when handling recurrent events see the [Recurrent Event Guide](recurrence.md) for more information
 - `autoAssignUid`: weather or not a uid should automatically assigned after querying a model, only set this to false when you manually assign a uid. If no uid field
 available this step will be skipped by default.
 - `uidField`: (optional) the name of the uid field
 
 In case your model extends `ContentActiveRecord` the query class provides a default implementation for the following filter:
 
 - `filterDashboard()`: this special filter function is used for the dashboard upcoming events snippets, by default this filter will make use of the `USER_RELATED_SCOPE_SPACE` and `USER_RELATED_SCOPE_OWN_PROFILE`
 - `filterGuests()`: used for guest users which are not able to use other filters
 - `filterUserRelated()`: used for user related queries e.g: 'Only content from following spaces' (see `ActiveQueryContent::userRelated`)
 - `filterContentContainer()`: used to filter content of a specific ContentContainer (Space/User)
 - `filterReadable()`: only include content readable by the  current user
 - `filterMine()`: only include items created by me
 - `setupDateCriteria()`: responsible for the date interval filter, this will only include items where either the start and/or the end date
 is within a given time range.
 
 Some filter have to be implemented manually (in case they are supported):
 
 - `filterIsParticipant()`: in case the item type supports an own participation logic, this filter is used to only include items
 in which the current logged in user participates (optional)
 - `filterResponded()`: **legacy** filter for filtering out items with no response yet (optional)
 - `filterNotResponded()`: **legacy** filter for filtering out items with a response (optional)
 
 >Note: In case a given filter is not supported the whole event item query will be skipped and will return a empty result.
 
 >Note: Guest users are not able to use other filters than the `filterGuests`
 
 >Info: Modules can decide which events to include or exclude in the Dashboard snippet by using the `filterDashboard` filter

The following example shows the implementation of a more complex AbstractCalendarQuery with custom start and end date field name
, custom date format and custom participation filter. 

**MeetingCalendarQuery example:**

```php
class MeetingCalendarQuery extends AbstractCalendarQuery
{
   
    protected static $recordClass = Meeting::class;

    public $startField = 'date';
    
    public $endField = 'date';
    
    public $dateFormat = 'Y-m-d';

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

The actual calendar event integration is handled by the `findItems` event of `humhub\modules\calendar\interfaces\CalendarService`.

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

> Note: The handlers `CalendarItemsEvent` either contains a `$contentContainer` for Space or user Profile related events or no 
`$contentContainer` for global search events.

### Allow Drag-Drop and Resize of calendar items

If you return an url in `getUpdateUrl()` and set the `isEditable()` of your event model to true, 
you have to implement a update controller function as the following:

**IndexController.php**

```php
public function actionCalendarUpdate($id)
{
    $this->forcePostRequest();

    $meeting = Meeting::find()->contentContainer($this->contentContainer)->where(['meeting.id' => $id])->one();

    if (!$meeting) {
        throw new HttpException('404');
    }

    if (!($meeting->content->canEdit())) {
        throw new HttpException('403');
    }

    $meetingForm = new MeetingForm(['meeting' => $meeting]);

    if ($meetingForm->updateTime(Yii::$app->request->post('start'), Yii::$app->request->post('end'))) {
        return $this->asJson(['success' => true]);
    }

    throw new HttpException(400, "Could not save! " . print_r($meetingForm->getErrors()));
}
```

**MeetingForm.php**

```php
public function updateTime($start = null, $end = null)
{
    $startDate = new DateTime($start, new DateTimeZone($this->getUserTimeZone()));
    $endDate = new DateTime($end, new DateTimeZone($this->getUserTimeZone()));

    Yii::$app->formatter->timeZone = Yii::$app->timeZone;

    // Note we ignore the end date (just use the time) since a meeting can't span over several days
    $this->meeting->date = Yii::$app->formatter->asDatetime($startDate, 'php:Y-m-d H:i:s');
    $this->meeting->begin = Yii::$app->formatter->asTime($startDate, 'php:H:i:s');
    $this->meeting->end = Yii::$app->formatter->asTime($endDate, 'php:H:i:s');

    return $this->meeting->save();
}
```

> Note: In the previous example we translate the given date from user timezone to app timezone and then save the item.
