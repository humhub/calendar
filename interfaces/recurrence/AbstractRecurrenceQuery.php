<?php


namespace humhub\modules\calendar\interfaces\recurrence;


use Yii;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\user\models\User;
use yii\base\Component;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

class AbstractRecurrenceQuery extends Component
{
    /**
     * @var string Defines the ActiveRecord class used for this query
     */
    protected static $recordClass;

    /**
     * @var string database field for start date
     */
    public $startField = 'start_datetime';

    /**
     * @var string database field for end date
     */
    public $endField = 'end_datetime';

    public $exdateField = 'exdate';

    /**
     * @var string
     */
    public $parentEventIdField = 'parent_event_id';

    public $recurrenceIdField = 'recurrence_id';

    public $idField = 'id';

    /**
     * @var RecurrentEventIF
     */
    public $event;

    /**
     * @param RecurrentEventIF $event
     * @return RecurrentEventIF[]
     * @throws \Throwable
     */
    public function getFollowingInstances()
    {
        if(RecurrenceHelper::isRecurrentRoot($this->event)) {
            $query = static::find()->where([$this->parentEventIdField => $this->event->getId()]);
        } else {
            // Make sure we use the original date, the start_date may have been overwritten
            $start_datetime = RecurrenceHelper::recurrenceIdToDate($this->event->getRecurrenceId());
            $query = static::find()->where([$this->parentEventIdField => $this->event->getRecurrenceRootId()])
                ->andWhere(['>', $this->startField, $start_datetime]);
        }

        return $query->orderBy($this->startField)->all();
    }

    /**
     * @param RecurrentEventIF $event
     * @return RecurrentEventIF
     * @throws \Throwable
     */
    public function getRecurrenceRoot()
    {
        return static::find()->where([$this->idField => $this->event->getRecurrenceRootId()])->one();
    }

    /**
     * @param RecurrentEventIF $event
     * @return bool
     */
    public function save()
    {
        if ($this->event instanceof ActiveRecord) {
            return $this->event->save();
        }
    }

    /**
     * @param RecurrentEventIF $event
     * @return false|int
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public function delete()
    {
        if ($this->event instanceof ActiveRecord) {
            return $this->event->delete();
        }
    }

    /**
     * @param $recurrent_id
     * @return RecurrentEventIF|null
     * @throws \Throwable
     */
    public function getRecurrenceInstance($recurrent_id)
    {
        return static::find()->where([$this->parentEventIdField => $this->event->getId(), $this->recurrenceIdField => $recurrent_id])->one();
    }

    /**
     * Static initializer.
     * @param User $user user instance used for some of the filter e.g. [[mine()]] by default current logged in user.
     * @return ActiveQuery
     * @throws \Throwable
     */
    public static function find()
    {
        return call_user_func(static::$recordClass . '::find');
    }

}