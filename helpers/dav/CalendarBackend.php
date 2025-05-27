<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav;

use humhub\helpers\ArrayHelper;
use humhub\libs\StringHelper;
use humhub\modules\calendar\helpers\dav\enum\EventProperty;
use humhub\modules\calendar\helpers\dav\enum\EventVirtualProperty;
use humhub\modules\calendar\helpers\dav\enum\EventVisibilityValue;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\integration\BirthdayCalendarEntry;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
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
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\PropPatch;
use Yii;
use Sabre\VObject\Reader;
use yii\db\ActiveRecord;

class CalendarBackend extends AbstractBackend implements SchedulingSupport
{
    private function sync(): EventSync
    {
        return Yii::createObject(EventSync::class);
    }

    private function properties(): EventProperties
    {
        return Yii::createObject(EventProperties::class);
    }

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
        $event = CalendarEntry::findOne(['uid' => $eventId]);

        if (!$event) {
            throw new NotFound();
        }

        if ($event && $event->content->canEdit()) {
            $event->delete();
        }
    }

    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $eventId = basename($objectUri, '.ics');

        if (CalendarEntry::find()->where(['uid' => $eventId])->exists()) {
            throw new Conflict();
        }

        /** @var ActiveRecord|CalendarEntry $event */
        $event = new CalendarEntry($this->getContentContainerForCalendar($calendarId));
        $this->mapVeventToEvent($calendarData, $event);

        if (!$event->content->canEdit()) {
            throw new Forbidden();
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $event->save();
            if ($event->hasErrors()) {
                throw new \RuntimeException();
            }
            $this->sync()->from($calendarData)->to($event);

            $transaction->commit();
        } catch (\Throwable $e) {
            Yii::error($e);
            $transaction->rollBack();

            throw new ServiceUnavailable;
        }

        $etag = md5($event->getLastModified()->getTimestamp());

        return "\"$etag\"";
    }


    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $eventId = basename($objectUri, '.ics');
        /** @var ActiveRecord|CalendarEntry $event */
        $event = CalendarEntry::findOne(['uid' => $eventId]);

        if (!$event) {
            throw new NotFound();
        }

        $this->mapVeventToEvent($calendarData, $event);

        if (!$event->content->canEdit()) {
            throw new Forbidden();
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $event->save();
            if ($event->hasErrors()) {
                throw new \RuntimeException();
            }
            $this->sync()->from($calendarData)->to($event);

            $transaction->commit();
        } catch (\Throwable $e) {
            Yii::error($e);
            $transaction->rollBack();

            throw new ServiceUnavailable;
        }

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


        $propPatch->handle($supportedProperties, function($mutations) use ($supportedProperties) {
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

    protected function mapVeventToEvent(string $data, CalendarEntry|ActiveRecord $event)
    {
        $properties = $this->properties()->from($data);

        if (
            $event->isNewRecord ||
            RichTextToPlainTextConverter::process($event->description) != $properties->get(EventProperty::DESCRIPTION)
        ) {
            $event->description = $properties->get(EventVirtualProperty::DESCRIPTION_NORMALIZED);
        }
        $event->title = $properties->get(EventProperty::TITLE);
        $event->all_day = $properties->get(EventVirtualProperty::ALL_DAY);
        $event->start_datetime = $properties->get(EventProperty::START_DATE)->format('Y-m-d H:i:s');
        $event->end_datetime = $properties->get(EventProperty::END_DATE)->format('Y-m-d H:i:s');
        $event->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $event->location = $properties->get(EventProperty::LOCATION);
        $event->uid = $properties->get(EventProperty::UID, $event->getUid());

        /** @var EventVisibilityValue $visibility */
        if (($visibility = $properties->get(EventProperty::VISIBILITY))) {
            $event->getContentRecord()->visibility = $visibility->contentType();
        }
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
        $vCalendar = Reader::read($objectData);

        $responses = [];
        if (!empty($vCalendar->VEVENT->ATTENDEE)) {
            foreach ($vCalendar->VEVENT->ATTENDEE as $attendee) {
                $email = str_replace('mailto:', '', $attendee->getValue());
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
