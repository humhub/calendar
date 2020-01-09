<?php


namespace humhub\modules\calendar\interfaces\recurrence;


use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\helpers\RRuleHelper;
use yii\base\Component;
use yii\db\StaleObjectException;

class RecurrenceService extends Component
{
    private static $deletedRoot = [];

    /**
     * @param RecurrentEventIF $original
     * @param RecurrentEventIF $event
     * @return bool|void
     * @throws \Throwable
     */
    public function splitRecurrentEvent(RecurrentEventIF $original, RecurrentEventIF $event)
    {
        if(RecurrenceHelper::isRecurrentRoot($event)) {
            return $this->updateAll($original, $event);
        }

        try {
            // Update until of old root
            $root = $event->getEventQuery()->getRecurrenceRoot();

            $isFirstInstanceEdit = RecurrenceHelper::getRecurrentId($root) === $event->getRecurrenceId();

            // Generate new UID
            $event->setUid(CalendarUtils::generateUUid());
            $event->setRecurrenceId(null);

            // Save this instance as a new recurrence root
            $event->setRecurrenceRootId(null);
            $event->getEventQuery()->save();

            $this->syncFollowingInstances($original, $event, true);

            if($isFirstInstanceEdit) {
                // We are editing the first instance, so we do not need the old root anymore
                // TODO: what about attached files?
                $root->getEventQuery()->delete();
            } else {
                $splitDate = $event->getStartDateTime()->modify('-1 hour');
                $root->setRrule(RRuleHelper::setUntil($root->getRrule(), $splitDate));
                $root->getEventQuery()->save();
            }
        } catch (\Exception $e) {
            \Yii::error($e);
            return false;
        }

        return true;
    }

    /**
     * @param RecurrentEventIF $original
     * @param RecurrentEventIF $event
     * @throws \Throwable
     */
    public function updateAll(RecurrentEventIF $original, RecurrentEventIF $event)
    {
        $event->getEventQuery()->save();
        $this->syncFollowingInstances($original, $event, false);
    }

    /**
     * @param RecurrentEventIF $original
     * @throws \Throwable
     */
    protected function syncFollowingInstances(RecurrentEventIF $original, RecurrentEventIF $event, $isSplit)
    {
        $followingInstances = $original->getEventQuery()->getFollowingInstances();

        // Sync following events
        if (!empty($followingInstances)) {
            $lastInstance = end($followingInstances);

            /**
             * If editMode = all -> $original is the root node and we want to sync all existing recurrence instances
             * If editMode = following -> $original is an old recurrent instance which itself should be excluded from sync
             */
            $searchStartDate = $isSplit ?  $original->getStartDateTime() : $original->getEndDateTime();

            // If not recurrent, we delete all following instances
            $remainingRecurrenceIds = !RecurrenceHelper::isRecurrent($event)
                ? []
                : RecurrenceHelper::getRecurrenceIds($event, $searchStartDate, $lastInstance->getEndDateTime());

            foreach ($followingInstances as $followingInstance) {
                if (!in_array($followingInstance->getRecurrenceId(), $remainingRecurrenceIds)) {
                    $followingInstance->getEventQuery()->delete();
                } else {
                    RecurrenceHelper::syncRecurrentEventData($event, $followingInstance);
                    $followingInstance->syncEventData($event);
                    $followingInstance->getEventQuery()->save();
                }
            }
        }
    }

    /**
     * @param RecurrentEventIF $event
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function onDelete(RecurrentEventIF $event)
    {
        $query = $event->getEventQuery();

        if(RecurrenceHelper::isRecurrentRoot($event)) {
            static::$deletedRoot[] = $event->getUid();
            foreach($query->getFollowingInstances() as $recurrence) {
                $recurrence->getEventQuery()->delete();
            }
        } elseif(RecurrenceHelper::isRecurrentInstance($event)) {
            $root = $query->getRecurrenceRoot();
            if($root && !in_array($root->getUid(), static::$deletedRoot, true)) {
                $root->setExdate(RecurrenceHelper::addExdates($root, $event));
                $root->getEventQuery()->save();
            }
        }

        static::$deletedRoot = [];
    }
}