<?php


namespace humhub\modules\calendar\models\reminder;


use humhub\components\ActiveRecord;
use humhub\components\behaviors\PolymorphicRelation;
use humhub\modules\calendar\interfaces\CalendarEntryIF;
use humhub\modules\calendar\interfaces\Remindable;
use humhub\modules\content\components\ContentActiveRecord;

/**
 * Class CalendarReminderSent
 * @package humhub\modules\calendar\interfaces
 *
 * @property int $id
 * @property int $content_id
 * @property int $reminder_id
 */
class CalendarReminderSent extends ActiveRecord
{
    /**
     * @param CalendarReminder $reminder
     * @param Remindable $entry
     * @return CalendarReminderSent
     */
    public static function create(CalendarReminder $reminder, Remindable $entry)
    {
        $instance = new static(['reminder_id' => $reminder->id]);
        $instance->content_id = $entry->getContentRecord()->id;
        $instance->save();

        return $instance;
    }

    public static function check(CalendarReminder $reminder, Remindable $entry = null)
    {
        return !empty(static::findByReminder($reminder, $entry)->all());
    }

    /**
     * @param CalendarReminder $reminder
     * @param Remindable $entry
     * @return \yii\db\ActiveQuery
     */
    public static function findByReminder(CalendarReminder $reminder, Remindable $entry = null)
    {
        $condition = ['reminder_id' => $reminder->id];
        if($entry) {
            $condition['content_id'] = $entry->getContentRecord()->id;
        }

        return static::find()->where($condition);
    }

}