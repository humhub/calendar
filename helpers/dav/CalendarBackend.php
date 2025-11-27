<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav;

use humhub\helpers\ArrayHelper;
use humhub\libs\StringHelper;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\dav\enum\EventProperty;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\integration\BirthdayCalendarEntry;
use humhub\modules\calendar\integration\BirthdayCalendarQuery;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModuleManager;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SchedulingSupport;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\PropPatch;
use Yii;
use yii\db\ActiveRecord;

class CalendarBackend extends AbstractBackend implements SchedulingSupport
{
    private function properties(): EventProperties
    {
        return Yii::createObject(EventProperties::class);
    }

    public function getCalendarsForUser($principalUri)
    {
        $userId = basename($principalUri);

        $user = User::find()->active()->andWhere(['username' => $userId])->one();

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

        return ArrayHelper::getColumn($contentContainers, function (array $contentContainer) use ($principalUri, $user) {
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
        $calendarId = trim((string) $calendarId, '/');

        $contentContainer = $this->getContentContainerForCalendar($calendarId);

        if (!$contentContainer) {
            throw new NotFound();
        }

        return ArrayHelper::getColumn(
            SyncService::instance()->getCalendarObjects($contentContainer),
            fn(CalendarEventIF $event) => $this->prepareEvent($event, $calendarId),
        );
    }

    public function getCalendarObject($calendarId, $objectUri)
    {
        $eventId = basename($objectUri, '.ics');

        if (StringHelper::startsWith($eventId, 'birthday')) {
            $userGuid = substr($eventId, 8);
            $birthdayUser = BirthdayCalendarQuery::find()->filterByGuid($userGuid)->query(true)->one();

            if ($birthdayUser) {
                $event = new BirthdayCalendarEntry(['model' => $birthdayUser]);
            } else {
                $event = null;
            }
        } else {
            $event = SyncService::instance()->getCalendarObject($eventId);
        }

        if (!$event) {
            throw new NotFound();
        }

        return $this->prepareEvent($event, $calendarId);
    }

    public function deleteCalendarObject($calendarId, $objectUri)
    {
        $eventId = basename($objectUri, '.ics');
        $object = SyncService::instance()->getCalendarObject($eventId);

        if (!$object) {
            throw new NotFound();
        }

        SyncService::instance()->deleteCalendarObject($object);
    }

    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $eventId = basename($objectUri, '.ics');

        if (CalendarEntry::find()->where(['uid' => $eventId])->exists()) {
            throw new Conflict();
        }

        /** @var ActiveRecord|CalendarEntry $object */
        $object = new CalendarEntry($this->getContentContainerForCalendar($calendarId));
        SyncService::instance()->createCalendarObject($object, $calendarData);
        $etag = md5((string) $object->getLastModified()->getTimestamp());

        return "\"$etag\"";
    }


    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $eventId = basename($objectUri, '.ics');
        $object = SyncService::instance()->getCalendarObject($eventId);

        if (!$object) {
            throw new NotFound();
        }

        SyncService::instance()->updateCalendarObject($object, $calendarData);

    }

    public function updateCalendar($calendarId, PropPatch $propPatch)
    {
        $contentContainer = $this->getContentContainerForCalendar($calendarId);

        if (!$contentContainer) {
            throw new NotFound();
        }

        $supportedProperties = [
            '{http://apple.com/ns/ical/}calendar-color',
            '{http://apple.com/ns/ical/}calendar-order',
        ];


        $propPatch->handle($supportedProperties, function ($mutations) use ($supportedProperties) {
            foreach ($mutations as $propertyName => $propertyValue) {
                if (in_array($propertyName, $supportedProperties)) {
                    // Ignore calendar-order and calendar-color for apple calendar
                    return true;
                }
            }

            return false;
        });
    }

    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        throw new NotImplemented();
    }

    public function deleteCalendar($calendarId)
    {
        throw new NotImplemented();
    }

    protected function prepareEvent(CalendarEventIF|CalendarEntry|BirthdayCalendarEntry $event, string $calendarId): array
    {
        if (RecurrenceHelper::isRecurrent($event) && !RecurrenceHelper::isRecurrentRoot($event)) {
            /* @var $event RecurrentEventIF */
            $event = $event->getRecurrenceQuery()->getRecurrenceRoot();
        }

        $ics = $event->hasMethod('generateIcs') ? $event->generateIcs() : CalendarUtils::generateIcs($event);

        return [
            'id'            => $event->uid,
            'uri'           => $event->uid . '.ics',
            'calendarid'    => $calendarId,
            'calendardata'  => $ics,
            'lastmodified'  => $event->getLastModified() ? $event->getLastModified()->getTimestamp() : null,
            'etag'          => $event->getLastModified() ? md5((string) $event->getLastModified()->getTimestamp()) : null,
            'size'          => strlen((string) $ics),
            'componenttype' => 'VEVENT',
            'firstoccurence' => $event->getStartDateTime()->getTimestamp(),
            'lastoccurence'  => $event->getEndDateTime()->getTimestamp(),
        ];
    }

    protected function getContentContainerForCalendar(string $calendarId): ?ContentContainerActiveRecord
    {
        if (StringHelper::startsWith($calendarId, 'profile')) {
            $contentContainer = User::find()->active()->andWhere(['guid' => substr($calendarId, 8)])->one();
        } elseif (StringHelper::startsWith($calendarId, 'space')) {
            $contentContainer = Space::findOne(['guid' => substr($calendarId, 6)]);
        } else {
            $contentContainer = null;
        }

        return $contentContainer;
    }

    public function getSchedulingObject($principalUri, $objectUri)
    {
        return null;
    }

    public function getSchedulingObjects($principalUri)
    {
        return [];
    }

    public function deleteSchedulingObject($principalUri, $objectUri)
    {
        throw new NotImplemented();
    }

    public function createSchedulingObject($principalUri, $objectUri, $objectData)
    {
        $responses = [];
        if (!empty($attendees = $this->properties()->from($objectData)->get(EventProperty::ATTENDEES))) {
            foreach ($attendees as $attendee) {
                $email = str_replace('mailto:', '', $attendee);
                $responses[] = [
                    'href' => "mailto:{$email}",
                    'status' => '2.0;Success',
                    'calendarData' => null,
                ];
            }
        }

        return $responses;
    }
}
