<?php


namespace humhub\modules\calendar\models\recurrence;


use humhub\modules\calendar\interfaces\recurrence\RecurrentCalendarEventIF;

class RecurrenceHelper
{
    public static function isRecurrent(RecurrentCalendarEventIF $evt)
    {
        return !empty($evt->getRrule());
    }

    public static function isRecurrentInstance(RecurrentCalendarEventIF $evt)
    {
        return static::isRecurrent($evt) && $evt->getRecurrenceId() && $evt->getParentId();
    }

    public static function isRecurrentRoot(RecurrentCalendarEventIF $evt)
    {
        return static::isRecurrent($evt) && !$evt->getParentId();
    }

}