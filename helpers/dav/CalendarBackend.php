<?php

namespace humhub\modules\calendar\helpers\dav;

use DateTime;
use humhub\helpers\ArrayHelper;
use humhub\libs\StringHelper;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\integration\BirthdayCalendarEntry;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModuleManager;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\PropPatch;
use Yii;
use Sabre\VObject\Reader;
use Sabre\VObject\Property;

class CalendarBackend extends AbstractBackend implements SyncSupport
{
    public function getCalendarsForUser($principalUri)
    {
        $userId = basename($principalUri);

        $user = User::findOne(['username' => $userId]);

        $contentContainers = [];
        if ($user->moduleManager->isEnabled('calendar') || $user->moduleManager->canEnable('calendar')) {
            $contentContainers[] = [
                'guid' => "profile-{$user->guid}",
                'name' => Yii::t('CalendarModule.base', '{name} Calendar', ['name' => 'Profile']),
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
                    'guid' => "space-{$space->guid}",
                    'name' => Yii::t('CalendarModule.base', '{name} Calendar', ['name' => $space->name]),
                ];
            }
        }

        return ArrayHelper::getColumn($contentContainers, function(array $contentContainer) use ($principalUri, $user) {
            $guid = ArrayHelper::getValue($contentContainer, 'guid');

            return [
                'id' => "$guid",
                'uri' => "$guid",
                'principaluri' => $principalUri,
                '{DAV:}displayname' => ArrayHelper::getValue($contentContainer, 'name'),
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT']),
                '{http://sabredav.org/ns}read-only' => 0,
            ];
        });
    }

    public function getCalendarObjects($calendarId)
    {
        $calendarId = trim($calendarId, '/');

        $contentContainer = $this->getContentContainerForCalendar($calendarId);

        if (!$contentContainer) {
            throw new NotFound();
        }

        /** @var CalendarService $calendarService */
        $calendarService = Yii::$app->moduleManager->getModule('calendar')->get(CalendarService::class);

        return ArrayHelper::getColumn(
            $calendarService->getCalendarItems(null, null, [], $contentContainer),
            function(CalendarEventIF $event) use ($calendarId) {
                return $this->prepareEvent($event, $calendarId);
            }
        );
    }

    public function getCalendarObject($calendarId, $objectUri)
    {
        $eventId = basename($objectUri, '.ics');

        if (StringHelper::startsWith($eventId, 'birthday')) {
            $userGuid = substr($eventId, 8);
            $birthdayUser = User::findOne(['guid' => $userGuid]);

            if ($birthdayUser) {
                $event = new BirthdayCalendarEntry(['model' => $birthdayUser]);
            } else {
                $event = null;
            }
        } else {
            $event = CalendarEntry::findOne(['uid' => $eventId]);
        }

        if (!$event) {
            throw new NotFound();
        }

        return $this->prepareEvent($event, $calendarId);
    }

    public function deleteCalendarObject($calendarId, $objectUri)
    {
        $eventId = basename($objectUri, '.ics');

        $event = CalendarEntry::findOne(['id' => $eventId]);


        if ($event && $event->content->canEdit()) {
            $event->delete();
        }
    }

    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $eventId = basename($objectUri, '.ics');

        if (CalendarEntry::find()->where(['guid' => $eventId])->exists()) {
            throw new Conflict();
        }

        $event = new CalendarEntry($this->getContentContainerForCalendar($calendarId));
        $this->mapVeventToEvent($calendarData, $event);
        $event->save();

        $etag = md5($event->getLastModified()->getTimestamp());

        return "\"$etag\"";
    }


    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $eventId = basename($objectUri, '.ics');
        $event = CalendarEntry::findOne(['guid' => $eventId]);

        if (!$event) {
            throw new NotFound();
        }

        $this->mapVeventToEvent($calendarData, $event);
        $event->save();

    }

    public function updateCalendar($calendarId, PropPatch $propPatch)
    {
        throw new MethodNotAllowed();
    }

    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null)
    {
        return [];
    }

    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        throw new NotImplemented();
    }

    public function deleteCalendar($calendarId)
    {
        throw new NotImplemented();
    }

    protected function prepareEvent(CalendarEventIF|CalendarEntry|BirthdayCalendarEntry $event, string $calendarId) : array
    {
        if (RecurrenceHelper::isRecurrent($event) && !RecurrenceHelper::isRecurrentRoot($event)) {
            /* @var $event RecurrentEventIF */
            $event = $event->getRecurrenceQuery()->getRecurrenceRoot();
        }

        $ics = $event->generateIcs();

        return [
            'id'            => $event->uid,
            'uri'           => $event->uid . '.ics',
            'calendarid'    => $calendarId,
            'calendardata'  => $ics,
            'lastmodified'  => $event->getLastModified() ? $event->getLastModified()->getTimestamp() : null,
            'etag'          => $event->getLastModified() ? md5($event->getLastModified()->getTimestamp()) : null,
            'size'          => strlen($ics),
            'componenttype' => 'VEVENT',
            'firstoccurence' => $event->getStartDateTime()->getTimestamp(),
            'lastoccurence'  => $event->getEndDateTime()->getTimestamp(),
        ];
    }

    protected function mapVeventToEvent(string $data, CalendarEventIF $event)
    {
        $data = Reader::read($data)->select('VEVENT')[0]->children();

        $eventData = ArrayHelper::map(
            $data,
            function(Property $property) {
                return $property->name;
            },
            function(Property $property) {
                return $property->getValue();
            }
        );

        $event->title = ArrayHelper::getValue($eventData, 'SUMMARY');
        $event->description = ArrayHelper::getValue($eventData, 'DESCRIPTION');
        $event->start_datetime = (new DateTime(ArrayHelper::getValue($eventData, 'DTSTART')))->format('Y-m-d H:i:s');
        $event->end_datetime = (new DateTime(ArrayHelper::getValue($eventData, 'DTEND')))->format('Y-m-d H:i:s');
        $event->all_day = 0;
        $event->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
//        $event->participant_info = null;
        $event->location = ArrayHelper::getValue($eventData, 'LOCATION');
        $event->uid = ArrayHelper::getValue($eventData, 'UID', $event->getUid());
    }

    protected function getContentContainerForCalendar(string $calendarId) : ?ContentContainerActiveRecord
    {
        if (StringHelper::startsWith($calendarId, 'profile')) {
            $contentContainer = User::findOne(['guid' => substr($calendarId, 8)]);
        } elseif (StringHelper::startsWith($calendarId, 'space')) {
            $contentContainer = Space::findOne(['guid' => substr($calendarId, 6)]);
        } else {
            $contentContainer = null;
        }

        return $contentContainer;
    }
}
