# Calendar interface v0.6

Since calendar module version 0.6 it is possible to injecting calendar items into the calendar and snippet.

All interface files reside within the `interface` directory of the calendar module.

## Exporting calendar item types

A calendar item type can be used to mark your exported calendar item. A meeting module for example
can export a `meeting` item type. Exported item types provide a config with the following options:

 - `title`: A translatable title
 - `color`: A default color used for this item type, which can be overwritten in the calendar module config
 - `icon`: Icon related to this event type

In order to export one or more item types, your Module has to implement a listener for the `humhub\modules\calendar\interfaces\CalendarService::getItemTypes` event as in the following example.

**config.php**:

```php
return [
    'id' => 'meeting',
    'class' => 'humhub\modules\meeting\Module',
    'namespace' => 'humhub\modules\meeting',
    'events' => [
        //...
        ['class' => 'humhub\modules\calendar\interfaces\CalendarService', 'event' => 'getItemTypes', 'callback' => ['humhub\modules\meeting\Events', 'onGetCalendarItemTypes']],
    ],
];
```

**Event.php:**

```php
public static function onGetCalendarItemTypes($event)
{
    $contentContainer = $event->contentContainer;

    if(!$contentContainer || $contentContainer->isModuleEnabled('meeting')) {
        $event->addType('meeting', [
            'title' => Yii::t('MeetingModule.base', 'Meeting'),
            'color' => static::DEFAULT_COLOR,
            'icon' => 'fa-calendar-o'
        ]);
    }
}
```

> Note: Don't forget to check if your module is enabled on the given `$event->contentContainer`. If no `contentContainer` is
given it's meant to be a global search for all available calendar item types.

## Inject calendar items

In order to inject calendar items into a calendar you have to implement a listener for `humhub\modules\calendar\interfaces\CalendarService::findItems` as in the following example.

**config.php**:

```php
return [
    //...
    'events' => [
        //...
        ['class' => 'humhub\modules\calendar\interfaces\CalendarService', 'event' => 'findItems', 'callback' => ['humhub\modules\meeting\Events', 'onFindCalendarItems']],
    ],
];
``` 

Items are appended by means of `$event->addItems($itemTypeKey, $itemsArray)`. The item array can contain the following values:

 - `start`: DateTime instance of the start time ideally with timezone (otherwise we assume app timezone).
 - `end`: DateTime instance of the end time ideally with timezone (otherwise we assume app timezone).
 - `allDay`: Boolean whether or not the events are all-day events.
 - `title`: The title of the given item, displayed in the calendar and snippet
 - `editable`: Whether or not this item is editable (resize/drag/drop) this will also require the updateUrl
 - `viewUrl`: This link will be loaded into a modal once the item is selected in the calendar
 - `openUrl`: A link to the actual content (e.g Permalink) used in the snippet
 - `icon`: An font awesome icon class as for example `fa-bell`, used to prepended an icon to the event dom element
 - `updateUrl`: A url used to directly update the start/end time in case `editable` is set to true

> Note: If you want to add full-day events, you must add a day to the end date and set the time to 00:00:00. 
The following example demonstrates this:

```php
// Example 1: We want to add an one-day all-day event: 01.01.2018
$start = new DateTime('2018-01-01 00:00:00')
$end = new DateTime('2018-01-02 00:00:00')  // one day longer, but time set to 00:00:00!
```

```php
// Example 2: We want to add a two-day all-day event: 01.01.2018 - 02.01.2018
$start = new DateTime('2018-01-01 00:00:00')
$end = new DateTime('2018-01-03 00:00:00')  // one day longer, but time set to 00:00:00!
```

**Event.php:**

```php
public static function onFindCalendarItems($event)
{
    $contentContainer = $event->contentContainer;

    if(!$contentContainer || $contentContainer->isModuleEnabled('meeting')) {
        /* @var $meetings Meeting[] */
        $meetings = MeetingCalendarQuery::findForEvent($event);

        $items = [];
        foreach ($meetings as $meeting) {
            $items[] = [
                'start' => $meeting->getBeginDateTime(),
                'end' => $meeting->getEndDateTime(),
                'title' => $meeting->title,
                'editable' => true,
                'icon' => 'fa-calendar-o',
                'viewUrl' => $meeting->content->container->createUrl('/meeting/index/modal', ['id' => $meeting->id, 'cal' => true]),
                'openUrl' => $meeting->content->getUrl(),
                'updateUrl' => $meeting->content->container->createUrl('/meeting/index/calendar-update', ['id' => $meeting->id]),
            ];
        }

        $event->addItems(static::ITEM_TYPE_KEY, $items);
    }
}
```

For filtering out Items which do not match our `$event->filters` we simply have to extend
 `humhub\modules\calendar\interfaces\AbstractCalendarQuery`. The subclass of this helper should overwrite the follwoing fields:
 
 - `recordClass`: a `ActiveRecord` class string used for initializing the query.
 - `startField`: the name of the database field for the start date
 - `endField`: the name of the database field for the end date, if there is no explicit end field use the start field
 - `dateFormat`: the database date format of your date fields 
 
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

**MeetingCalendarQuery:**

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

### Allow Drag-Drop and Resize of calendar items

If you provide a `updateUrl` and set the `editable` to true, you have to implement a update controller function as the following:

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
