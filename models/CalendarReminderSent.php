<?php


namespace humhub\modules\calendar\models;


use humhub\components\ActiveRecord;
use humhub\modules\content\components\ContentActiveRecord;

/**
 * Class CalendarReminderSent
 * @package humhub\modules\calendar\interfaces
 *
 * @property string $object_model
 * @property int $object_id
 * @property int $reminder_id
 */
class CalendarReminderSent extends ActiveRecord
{
    /**
     * @param CalendarReminder $reminder
     * @param ContentActiveRecord|null $entry
     * @return CalendarReminderSent
     */
    public static function create(CalendarReminder $reminder, ContentActiveRecord $entry = null)
    {
        $instance = new static(['reminder_id' => $reminder->id]);
        $instance->setPolymorphicRelation($entry);
        $instance->save();

        return $instance;
    }

    public static function check(CalendarReminder $reminder, ContentActiveRecord $entry = null)
    {
        return !empty(static::findByReminder($reminder, $entry)->all());
    }

    /**
     * @param CalendarReminder $reminder
     * @param ContentActiveRecord $entry
     * @return \yii\db\ActiveQuery
     */
    public static function findByReminder(CalendarReminder $reminder, ContentActiveRecord $entry = null)
    {
        $condition = ['reminder_id' => $reminder->id];
        if($entry) {
            $condition['object_model'] = get_class($entry);
            $condition['object_id'] =  $entry->getPrimaryKey();
        }

        return static::find()->where($condition);
    }

}