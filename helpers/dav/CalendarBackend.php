<?php

namespace humhub\modules\calendar\helpers\dav;

use humhub\helpers\ArrayHelper;
use humhub\libs\StringHelper;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\event\AbstractCalendarQuery;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\fullcalendar\FullCalendar;
use humhub\modules\content\components\ContentContainerModuleManager;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
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

        $user = User::findOne(['id' => $userId]);

        $contentContainers = [];
        if ($user->moduleManager->isEnabled('calendar') || $user->moduleManager->canEnable('calendar')) {
            $contentContainers[] = [
                'id' => "profile-{$user->guid}",
                'name' => Yii::t('CalendarModule.base', 'Humhub {name} Calendar', ['name' => 'Profile']),
            ];
        }

        $calendarMemberSpaceQuery = Membership::getUserSpaceQuery($user);

        if (!ContentContainerModuleManager::getDefaultState(Space::class, 'calendar')) {
            $calendarMemberSpaceQuery
                ->leftJoin(
                    'contentcontainer_module cm',
                    'cm.module_id = :calendar AND cm.contentcontainer_id = space.contentcontainer_id',
                    [':calendar' => 'calendar'],
                )
                ->andWhere('cm.module_id IS NOT NULL')
                ->andWhere(['cm.module_state' => ContentContainerModuleState::STATE_ENABLED]);
        }

        foreach ($calendarMemberSpaceQuery->all() as $space) {
            if ((new CalendarEntry($space))->content->canEdit()) {
                $contentContainers[] = [
                    'id' => "space-{$space->guid}",
                    'name' => Yii::t('CalendarModule.base', 'Humhub {name} Calendar', ['name' => $space->name]),
                ];
            }
        }

        return ArrayHelper::getColumn($contentContainers, function(array $contentContainer) use ($principalUri) {
            $id = ArrayHelper::getValue($contentContainer, 'id');

            return [
                'id'           => $id,
                'uri'          => "humhub-calendar-$id",
                'principaluri' => $principalUri,
                'displayname'  => ArrayHelper::getValue($contentContainer, 'name'),
                'timezone'     => 'UTC',
                'components'   => ['VEVENT'],
            ];
        });
    }

    public function getCalendarObjects($calendarId)
    {
        if (StringHelper::startsWith($calendarId, 'profile')) {
            $contentContainer = User::findOne(['guid' => substr($calendarId, 8)]);
        } elseif (StringHelper::startsWith($calendarId, 'space')) {
            $contentContainer = Space::findOne(['guid' => substr($calendarId, 6)]);
        } else {
            return null;
        }

        /** @var CalendarService $calendarService */
        $calendarService = Yii::$app->moduleManager->getModule('calendar')->get(CalendarService::class);

        return ArrayHelper::getColumn(
            $calendarService->getCalendarItems(null, null, [], $contentContainer),
            function(CalendarEntry $entry) use ($calendarId) {
                $ics = $entry->generateIcs();

                return [
                    'id'            => $entry->uid,
                    'uri'           => $entry->uid . '.ics',
                    'calendarid'    => $calendarId,
                    'calendardata'  => $ics,
                    'lastmodified'  => strtotime($entry->start_datetime),
//                    'etag'          => md5($entry->updated_at),
                    'size'          => strlen($ics),
                    'componenttype' => 'VEVENT',
                    'firstoccurence' => strtotime($entry->start_datetime),
                    'lastoccurence'  => strtotime($entry->end_datetime),
                ];
        });
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