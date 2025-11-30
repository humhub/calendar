<?php

namespace humhub\modules\calendar\helpers\dav;

use humhub\modules\calendar\helpers\dav\enum\EventProperty;
use humhub\modules\calendar\helpers\dav\enum\EventVirtualProperty;
use humhub\modules\calendar\helpers\dav\enum\EventVisibilityValue;
use humhub\modules\calendar\helpers\dav\event\DeleteObjectEvent;
use humhub\modules\calendar\helpers\dav\event\GetObjectEvent;
use humhub\modules\calendar\helpers\dav\event\UpdateObjectEvent;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\event\legacy\CalendarEventIFWrapper;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\ServiceUnavailable;
use Yii;
use yii\base\Component;
use yii\base\StaticInstanceInterface;
use yii\base\StaticInstanceTrait;
use yii\db\ActiveRecord;
use humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter;

class SyncService extends Component implements StaticInstanceInterface
{
    use StaticInstanceTrait;
    private ?CalendarService $calendarService;

    public function init()
    {
        parent::init();

        $this->calendarService = Yii::$app->moduleManager->getModule('calendar')->get(CalendarService::class);
    }

    private function sync(): EventSync
    {
        return Yii::createObject(EventSync::class);
    }

    private function properties(): EventProperties
    {
        return Yii::createObject(EventProperties::class);
    }

    public const EVENT_GET_OBJECT = 'event_get_object';
    public const EVENT_UPDATE_OBJECT = 'event_update_object';
    public const EVENT_DELETE_OBJECT = 'event_delete_object';

    public function getCalendarObjects($contentContainer)
    {
        return $this->calendarService->getCalendarItems(null, null, [], $contentContainer);
    }

    /**
     * @param $objectId
     * @return ActiveRecord|CalendarEntry|CalendarEventIFWrapper
     */
    public function getCalendarObject($objectId)
    {
        $object = CalendarEntry::findOne(['uid' => $objectId]);

        if (!$object) {
            $event = new GetObjectEvent();
            $event->objectId = $objectId;
            $this->trigger($event->name, $event);
            $object = $event->object;
        }

        return $object;
    }

    public function createCalendarObject($object, $data)
    {
        $properties = $this->properties()->from($data);

        $this->mapVeventToEvent($properties, $object);

        if (!$object->content->canEdit()) {
            throw new Forbidden();
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $object->save();
            if ($object->hasErrors()) {
                throw new \RuntimeException('Failed to save an event: ' . http_build_query($object->firstErrors));
            }
            $this->sync()->from($properties)->to($object);

            $transaction->commit();
        } catch (\Throwable $e) {
            if (YII_DEBUG) {
                throw $e;
            }

            Yii::error($e);
            $transaction->rollBack();

            throw new ServiceUnavailable();
        }

        return $object;
    }

    public function updateCalendarObject($object, $data)
    {
        $properties = $this->properties()->from($data);

        $event = Yii::createObject(UpdateObjectEvent::class);
        $event->object = $object;
        $event->properties = $properties;
        $this->trigger($event->name, $event);

        if (!$object instanceof CalendarEntry) {
            return;
        }

        $this->mapVeventToEvent($properties, $object);

        if (!$object->content->canEdit()) {
            throw new Forbidden();
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $object->save();
            if ($object->hasErrors()) {
                throw new \RuntimeException('Failed to save an event: ' . http_build_query($object->firstErrors));
            }
            $this->sync()->from($this->properties()->from($data))->to($object);

            $transaction->commit();
        } catch (\Throwable $e) {
            if (YII_DEBUG) {
                throw $e;
            }

            Yii::error($e);
            $transaction->rollBack();

            throw new ServiceUnavailable();
        }
    }

    public function deleteCalendarObject($object)
    {
        $event = Yii::createObject(DeleteObjectEvent::class);
        $event->object = $object;
        $this->trigger($event->name, $event);

        if ($object instanceof CalendarEntry && $object->content->canEdit()) {
            $object->delete();
        }
    }

    protected function mapVeventToEvent(EventProperties $properties, CalendarEntry|ActiveRecord $event)
    {
        if (
            $event->isNewRecord
            || RichTextToPlainTextConverter::process($event->description) != $properties->get(EventProperty::DESCRIPTION)
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

        if (empty(trim((string) $event->title))) {
            $event->title = '-';
        }

        /** @var EventVisibilityValue $visibility */
        if (($visibility = $properties->get(EventProperty::VISIBILITY))) {
            $event->getContentRecord()->visibility = $visibility->contentType();
        }
    }
}
