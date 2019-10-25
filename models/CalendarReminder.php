<?php


namespace humhub\modules\calendar\models;


use DateTime;
use humhub\components\ActiveRecord;
use humhub\components\behaviors\PolymorphicRelation;
use humhub\modules\calendar\interfaces\CalendarItem;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\user\models\User;
use yii\db\Expression;

/**
 * Class CalendarReminder
 *
 * Types of reminder:
 *
 * # Global Default Reminder
 *
 * - unit
 * - value
 * - object_model: null
 * - object_id: null
 * - contentcontainer_id: null
 * - active: true
 *
 * # Space Default Reminder
 *
 * - unit
 * - value
 * - object_model: null
 * - object_id: null
 * - contentcontainer_id: space container id
 * - active: true
 *
 * # Space Level Model Exception
 *
 * - unit
 * - value
 * - object_model: Model class
 * - object_id: Model id
 * - contentcontainer_id: null   // Note this is required for easy seperation of space level and user level exceptions
 * - active: true
 *
 * # Space Level Model Exception
 *
 * - unit
 * - value
 * - object_model: Model class
 * - object_id: Model id
 * - contentcontainer_id: User container id
 * - active: true
 *
 * @package humhub\modules\calendar\models
 *
 * @property integer id
 * @property string value
 * @property integer unit
 * @property string object_model
 * @property integer object_id
 * @property integer sent
 * @property integer contentcontainer_id
 * @property integer active
 */
class CalendarReminder extends ActiveRecord
{
    const UNIT_HOUR = 1;
    const UNIT_DAY = 2;
    const UNIT_WEEK = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'calendar_reminder';
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->active === null) {
            $this->active = 1;
        }
    }

    public function rules()
    {
        $rules = [
            [['unit'], 'in', 'range' => [static::UNIT_HOUR, static::UNIT_DAY, static::UNIT_WEEK]],
            [['value'], 'integer', 'min' => 1, 'max' => '30']
        ];

        if ($this->active) {
            $rules[] = [['unit', 'value'], 'required'];
        }

        return $rules;
    }

    public function behaviors()
    {
        return [
            [
                'class' => PolymorphicRelation::class,
                'mustBeInstanceOf' => [ContentActiveRecord::class]
            ]
        ];
    }

    /**
     * @param $unit
     * @param $value
     * @return CalendarReminder
     */
    public static function initGlobalDefault($unit, $value)
    {
        return new static(['unit' => $unit, 'value' => $value, 'active' => true]);
    }

    /**
     * @param $unit
     * @param $value
     * @param ContentContainerActiveRecord $container
     * @return CalendarReminder
     */
    public static function initContainerDefault($unit, $value, ContentContainerActiveRecord $container)
    {
        return new static([
            'unit' => $unit,
            'value' => $value,
            'contentcontainer_id' => $container->contentcontainer_id,
            'active' => true
        ]);
    }

    /**
     * @param $unit
     * @param $value
     * @param ContentActiveRecord $model
     * @param User|null $user
     * @return CalendarReminder
     */
    public static function initEntryLevel($unit, $value, ContentActiveRecord $model, User $user = null)
    {
        $instance = new static(['unit' => $unit, 'value' => $value, 'active' => true]);
        $instance->setPolymorphicRelation($model);

        if($user) {
            $instance->contentcontainer_id = $user->contentcontainer_id;
        }

        return $instance;
    }

    /**
     * @return bool
     */
    public function isUserLevelReminder()
    {
        return $this->object_model !== null && $this->object_id !== null && $this->contentcontainer_id !== null;
    }

    /**
     * @param ContentContainerActiveRecord|null $container
     * @return static[]
     */
    public static function getDefaults(ContentContainerActiveRecord $container = null, $globalFallback = false)
    {
        $query = static::find();

        if ($container) {
            $query->andWhere(['contentcontainer_id' => $container->contentcontainer_id]);
        } else {
            $query->andWhere(['IS', 'contentcontainer_id', new Expression('NULL')]);
        }

        $result = $query->all();

        if (empty($result) && $container && $globalFallback) {
            return static::getDefaults();
        }

        return $result;
    }

    /**
     * @param ContentContainerActiveRecord|null $container
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function clearDefaults(ContentContainerActiveRecord $container = null)
    {
        foreach (static::getDefaults($container)->all() as $reminder) {
            $reminder->delete();
        }
    }

    /**
     * Finds reminder by model, this does not include default reminders.
     *
     * @param ContentActiveRecord $model
     * @return CalendarReminder[]
     */
    public static function getByModel(ContentActiveRecord $model)
    {
        return static::find()->where(['object_id' => $model->id, 'object_model' => get_class($model)])->orderBy('calendar_reminder.contentcontainer_id DESC')->all();
    }

    /**
     * Checks the due date of the reminder message.
     * @param CalendarItem $model
     * @return bool
     * @throws \Exception
     */
    public function checkMaturity(CalendarItem $model)
    {
        if (!$this->active) {
            return false;
        }

        // TODO: Check if already sent ReminderSent (unit, value, object_model, object_id)
        $sentDate = $model->getStartDateTime()->modify($this->getModify());

        return $sentDate <= new DateTime();

    }

    private function getModify()
    {
        switch ($this->unit) {
            case static::UNIT_HOUR:
                $modifyUnit = 'hours';
                break;
            case static::UNIT_DAY:
                $modifyUnit = 'days';
                break;
            case static::UNIT_WEEK:
                $modifyUnit = 'weeks';
                break;
            default:
                $modifyUnit = 'hours';
        }

        // add tolerance for cron delay....
        return '-'.$this->value.' '.$modifyUnit;
    }
}