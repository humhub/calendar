<?php

namespace humhub\modules\calendar\helpers\dav;

use humhub\modules\calendar\helpers\dav\event\GetObjectEvent;
use humhub\modules\calendar\integration\BirthdayCalendarEntry;
use humhub\modules\calendar\integration\BirthdayCalendarQuery;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\event\legacy\CalendarEventIFWrapper;
use humhub\modules\calendar\models\CalendarEntry;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ServiceUnavailable;
use Yii;
use yii\base\Component;
use yii\base\StaticInstanceInterface;
use yii\base\StaticInstanceTrait;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class SyncService extends Component implements StaticInstanceInterface
{
    private ?CalendarService $calendarService;

    use StaticInstanceTrait;

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

    const EVENT_GET_OBJECT = 'event_get_object';
    const EVENT_CREATE_OBJECT = 'event_create_object';
    const EVENT_UPDATE_OBJECT = 'event_update_object';
    const EVENT_DELETE_OBJECT = 'event_delete_object';

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

    public function updateCalendarObject($object, $data)
    {
        $this->mapVeventToEvent($data, $object);

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
}