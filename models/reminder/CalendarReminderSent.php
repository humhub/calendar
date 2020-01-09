<?php


namespace humhub\modules\calendar\models\reminder;


use humhub\components\ActiveRecord;
use humhub\modules\calendar\interfaces\reminder\CalendarEventReminderIF;

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
     * @param CalendarEventReminderIF $entry
     * @return CalendarReminderSent
     */
    public static function create(CalendarReminder $reminder, CalendarEventReminderIF $entry)
    {
        $instance = new static(['reminder_id' => $reminder->id]);
        $instance->content_id = $entry->getContentRecord()->id;
        $instance->save();

        return $instance;
    }

    public static function check(CalendarReminder $reminder, CalendarEventReminderIF $entry = null)
    {
        return !empty(static::findByReminder($reminder, $entry)->all());
    }

    /**
     * @param CalendarReminder $reminder
     * @param CalendarEventReminderIF $entry
     * @return \yii\db\ActiveQuery
     */
    public static function findByReminder(CalendarReminder $reminder, CalendarEventReminderIF $entry = null)
    {
        $condition = ['reminder_id' => $reminder->id];
        if($entry) {
            $condition['content_id'] = $entry->getContentRecord()->id;
        }

        return static::find()->where($condition);
    }

}