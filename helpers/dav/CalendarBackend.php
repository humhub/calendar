<?php

namespace humhub\modules\calendar\helpers\dav;

use humhub\modules\calendar\models\CalendarEntry;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Yii;

class CalendarBackend extends AbstractBackend implements SyncSupport
{
    public function getCalendarsForUser($principalUri)
    {
        $userId = basename($principalUri);

        return [
            [
                'id'           => $userId,
                'uri'          => 'humhub-calendar-' . $userId,
                'principalUri' => $principalUri,
                'displayname'  => 'HumHub Calendar',
                'timezone'     => 'UTC',
                'components'   => ['VEVENT'],
            ],
        ];
    }

    public function getCalendarObjects($calendarId)
    {
        /** @var CalendarEntry[] $events */
        $events = CalendarEntry::find()->all();

        $objects = [];
        foreach ($events as $event) {
            $ics = $event->generateIcs();

            $objects[] = [
                'id'            => $event->id,
                'uri'           => $event->id . '.ics',
                'calendarid'    => $calendarId,
                'calendardata'  => $ics,
                'lastmodified'  => strtotime($event->updated_at),
                'etag'          => md5($event->updated_at),
                'size'          => strlen($ics),
                'componenttype' => 'VEVENT',
                'firstoccurence' => strtotime($event->start_datetime),
                'lastoccurence'  => strtotime($event->end_datetime),
            ];
        }

        return $objects;
    }

    public function getCalendarObject($calendarId, $objectUri)
    {
        $eventId = basename($objectUri, '.ics');
        $event = CalendarEntry::findOne(['id' => $eventId]);

        if (!$event) {
            throw new NotFound("Event not found");
        }

        $ics = $event->generateIcs();

        return [
            'id'            => $event->id,
            'uri'           => $event->id . '.ics',
            'calendarid'    => $calendarId,
            'calendardata'  => $ics,
            'lastmodified'  => strtotime($event->updated_at),
            'etag'          => md5($event->updated_at),
            'size'          => strlen($ics),
            'componenttype' => 'VEVENT',
            'firstoccurence' => strtotime($event->start_datetime),
            'lastoccurence'  => strtotime($event->end_datetime),
        ];
    }

    public function deleteCalendarObject($calendarId, $objectUri)
    {
        $eventId = basename($objectUri, '.ics');
        $event = CalendarEntry::findOne(['id' => $eventId]);

        if ($event) {
            $event->delete();
        }
    }

    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $eventId = basename($objectUri, '.ics');
        $event = CalendarEntry::findOne(['id' => $eventId]);

        if (!$event) {
            $event = new CalendarEntry();
            $event->created_by = $calendarId;
        }

        if (preg_match('/SUMMARY:(.*)/', $calendarData, $matches)) {
            $event->title = trim($matches[1]);
        }
        if (preg_match('/DTSTART:(\d+T\d+Z)/', $calendarData, $matches)) {
            $event->start_datetime = gmdate('Y-m-d H:i:s', strtotime($matches[1]));
        }
        if (preg_match('/DTEND:(\d+T\d+Z)/', $calendarData, $matches)) {
            $event->end_datetime = gmdate('Y-m-d H:i:s', strtotime($matches[1]));
        }

        $event->save();
    }

    public function updateCalendar($calendarId, PropPatch $propPatch)
    {
        throw new MethodNotAllowed('Calendar update is not supported.');
    }

    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null)
    {
        return [];
    }

    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        throw new MethodNotAllowed('Calendar creation is not supported.');
    }

    public function deleteCalendar($calendarId)
    {
        throw new MethodNotAllowed('Calendar deletion is not supported.');
    }

    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $eventId = basename($objectUri, '.ics');
        $event = CalendarEntry::findOne(['id' => $eventId]);

        if (!$event) {
            throw new NotFound("Event not found.");
        }

        // Parse iCalendar data and update event details
        if (preg_match('/SUMMARY:(.*)/', $calendarData, $matches)) {
            $event->title = trim($matches[1]);
        }
        if (preg_match('/DTSTART:(\d+T\d+Z)/', $calendarData, $matches)) {
            $event->start_datetime = gmdate('Y-m-d H:i:s', strtotime($matches[1]));
        }
        if (preg_match('/DTEND:(\d+T\d+Z)/', $calendarData, $matches)) {
            $event->end_datetime = gmdate('Y-m-d H:i:s', strtotime($matches[1]));
        }

        $event->save();
    }
}