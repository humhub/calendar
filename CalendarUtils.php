<?php

namespace humhub\modules\calendar;

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

    /**
     *
     * @param DateTime $date1
     * @param DateTime $date2
     * @param type $endDateMomentAfter
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
}
