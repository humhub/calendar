<?php

namespace humhub\modules\calendar\helpers;

use DateTime;
use DateTimeZone;
use Yii;

/**
 * Description of CalendarUtils
 *
 * @author luke
 */
class CalendarUtils
{

    private static $userTimezone;

    const DB_DATE_FORMAT = 'Y-m-d H:i:s';
    const ICAL_TIME_FORMAT        = 'Ymd\THis';

    const DOW_SUNDAY = 1 ;
    const DOW_MONDAY = 2 ;
    const DOW_TUESDAY = 3 ;
    const DOW_WEDNESDAY = 4 ;
    const DOW_THURSDAY = 5 ;
    const DOW_FRIDAY = 6 ;
    const DOW_SATURDAY = 7 ;

    /**
     *
     * @param DateTime $date1
     * @param DateTime $date2
     * @param bool $endDateMomentAfter
     * @return boolean
     */
    public static function isFullDaySpan(DateTime $date1, DateTime $date2, $endDateMomentAfter = false)
    {
        $dateInterval = $date1->diff($date2, true);

        if ($endDateMomentAfter) {
            if ($dateInterval->days > 0 && $dateInterval->h == 0 && $dateInterval->i == 0 && $dateInterval->s == 0) {
                return true;
            }
        } else {
            if ($dateInterval->h == 23 && $dateInterval->i == 59) {
                return true;
            }
        }


        return false;
    }

    public static function cleanRecurrentId($recurrentId, $targetTZ = null)
    {
        $date = ($recurrentId instanceof \DateTimeInterface) ? $recurrentId : new DateTime($recurrentId, new DateTimeZone('UTC'));

        if($targetTZ) {
            $date->setTimezone(new DateTimeZone($targetTZ));
        }

        return $date->format(static::ICAL_TIME_FORMAT);
    }

    /**
     * @return DateTimeZone
     */
    public static function getUserTimeZone()
    {
        if(!static::$userTimezone) {
            $tz =  Yii::$app->user->isGuest
                ? Yii::$app->timeZone
                : Yii::$app->user->getTimeZone();

            if(!$tz) {
                $tz = Yii::$app->timeZone;
            }

            if($tz) {
                static::$userTimezone = new DateTimeZone($tz);
            }
        }

        return static::$userTimezone;
    }

    public static function formatDateTimeToAppTime($string)
    {
        $timezone = new DateTimeZone(Yii::$app->timeZone);
        $datetime = new DateTime($string);
        return $datetime->setTimezone($timezone);
    }

    public static function toDBDateFormat($date, $fixed = false)
    {
        if(!$date) {
            return null;
        }

        if(is_string($date)) {
            $date = new DateTime($date);
        }

        if(!$fixed) {
            $date->setTimezone(new DateTimeZone(Yii::$app->timeZone));
        }

        return $date->format(static::DB_DATE_FORMAT);
    }

    public static function getFirstDayOfWeek()
    {
        if (extension_loaded('intl')) {
            $cal = \IntlCalendar::createInstance(null, Yii::$app->formatter->locale);
            return $cal->getFirstDayOfWeek();
        }

        return static::DOW_MONDAY;
    }

    public static function getDaysOfWeek()
    {
        $days = [
            static::DOW_SUNDAY => Yii::t('CalendarModule.base', 'Sunday'),
            static::DOW_MONDAY => Yii::t('CalendarModule.base', 'Monday'),
            static::DOW_TUESDAY => Yii::t('CalendarModule.base', 'Tuesday'),
            static::DOW_WEDNESDAY => Yii::t('CalendarModule.base', 'Wednesday'),
            static::DOW_THURSDAY => Yii::t('CalendarModule.base', 'Thursday'),
            static::DOW_FRIDAY => Yii::t('CalendarModule.base', 'Friday'),
            static::DOW_SATURDAY => Yii::t('CalendarModule.base', 'Saturday'),
        ];

        return $days;
    }

    public static function getDayOfWeek($dow)
    {
        $dows = static::getDaysOfWeek();
        if(isset($dow, $dows)) {
            return $dows[$dow];
        }
    }
}
