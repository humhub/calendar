## Recurrent Events

Calendar Module v1.0 added the support of recurrent events.

### Implement custom recurrent events

A custom module can implement recurrent events by:

### Own implementation: 

No use of any recurrent event related calendar interfaces. The module is responsible for creating
expanding, editing, deleting of recurrent events. The normal event interface is used.

### Simple recurring event interface:

By implementing the `humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF` on your event model, you can facilitate
some of the recurrent event features of the calendar module.



When working with `ActiveRecords` as recurrent event model, y
