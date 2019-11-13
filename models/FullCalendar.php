<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models;


use humhub\modules\calendar\interfaces\CalendarEventStatusIF;
use Yii;
use DateTime;
use humhub\modules\calendar\interfaces\CalendarEventIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrentCalendarEvent;
use humhub\libs\Html;

class FullCalendar
{
    /**
     * @param CalendarEntry $entry
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public static function getFullCalendarArray(CalendarEventIF $entry)
    {
        $result = [
            'uid' => $entry->getUid(),
            'title' => static::getTitle($entry),
            'editable' => $entry->isEditable(),
            'backgroundColor' => Html::encode($entry->getColor()),
            'allDay' => $entry->isAllDay(),
            'updateUrl' => $entry->getUpdateUrl(),
            'viewUrl' => $entry->getCalendarViewUrl(),
            'viewMode' => $entry->getCalendarViewMode(),
            'icon' => $entry->getIcon(),
            'start' => static::toFullCalendarFormat($entry->getStartDateTime()),
            'end' => static::toFullCalendarFormat(static::getEndDate($entry)),
            'eventDurationEditable' => true,
            'eventStartEditable' => true
        ];

        if($entry instanceof RecurrentCalendarEvent) {
            $result['rrule'] = $entry->getRrule();
            $result['exdate'] = $entry->getExdate();
        }

        return $result;
    }

    private static function getEndDate(CalendarEventIF $entry)
    {
        $endDateTime = clone $entry->getEndDateTime();

        if($entry->isAllDay()) {
            // Note: In fullcalendar the end time is the moment AFTER the event.
            // But we store the exact event time 00:00:00 - 23:59:59 so add some time to the full day event.
            $endDateTime->add(new \DateInterval('PT2H'))->setTime(0,0,0);
        }

        return $endDateTime;
    }

    private static function getTitle(CalendarEventIF $entry)
    {
        $title = $entry->getTitle();

        if($entry instanceof CalendarEventStatusIF && $entry->getEventStatus() === CalendarEventStatusIF::STATUS_CANCELLED) {
            $title .= ' ('.Yii::t('CalendarModule.base', 'canceled').')';
        }

        return $title;
    }

    public static function toFullCalendarFormat(DateTime $dt)
    {
        return Yii::$app->formatter->asDatetime($dt, 'php:c');
    }
}