<?php

namespace humhub\modules\calendar\helpers;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use humhub\libs\DateHelper;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\event\EditableEventIF;
use humhub\modules\content\models\Content;
use Sabre\VObject\UUIDUtil;
use Yii;

/**
 * Description of CalendarUtils
 *
 * @author luke
 */
class CalendarUtils
{

    /**
     * Database Field - Validators
     */
    const REGEX_DBFORMAT_DATE = '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/';
    const REGEX_DBFORMAT_DATETIME = '/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/';

    private static $userTimezone;
    private static $userTimezoneString;

    const DATE_FORMAT_ATOM = 'Y-m-d\TH:i:sP';
    const DB_DATE_FORMAT = 'Y-m-d H:i:s';
    const DATE_FORMAT_SHORT = 'Y-m-d';
    const DATE_FORMAT_SHORT_NO_TIME = '!Y-m-d';

    const TIME_FORMAT_SHORT_PHP = 'H:i';
    const TIME_FORMAT_SHORT = 'php:'.self::TIME_FORMAT_SHORT_PHP;
    const TIME_FORMAT_SHORT_MERIDIEM_PHP = 'h:i A';
    const TIME_FORMAT_SHORT_MERIDIEM = 'php:'.self::TIME_FORMAT_SHORT_MERIDIEM_PHP;

    const ICAL_TIME_FORMAT        = 'Ymd\THis';

    const DOW_SUNDAY = 1;
    const DOW_MONDAY = 2;
    const DOW_TUESDAY = 3;
    const DOW_WEDNESDAY = 4;
    const DOW_THURSDAY = 5;
    const DOW_FRIDAY = 6;
    const DOW_SATURDAY = 7;

    /**
     * @param $value
     * @param null $timeValue
     * @param null $timeFormat
     * @param string $timeZone
     * @return bool|DateTime
     * @throws \Exception
     */
    public static function parseDateTimeString($value, $timeValue = null, $timeFormat = null, $timeZone = 'UTC')
    {
        if(static::isInDbFormat($value)) {
            $date = DateTime::createFromFormat(static::DATE_FORMAT_SHORT_NO_TIME, $value, static::getDateTimeZone('UTC'));
            $ts = $date->getTimestamp();
        } else {
            $ts = DateHelper::parseDateTimeToTimestamp($value);
        }

        if(!$ts) {
            return false;
        }

        if($timeValue) {
            $ts += static::parseTime($timeValue, $timeFormat);
        }

        $date = new DateTime(null, new DateTimeZone('UTC'));
        $date->setTimestamp($ts);

        $result = DateTime::createFromFormat(static::DB_DATE_FORMAT, static::toDBDateFormat($date), static::getDateTimeZone($timeZone));

        /**
         * We check for year 1980 regarding a Bug in HumHub 1.3 which returned unix epoch date for invalid date formats with time
         * This was fixed in HumHub 1.4
         */
        if($result < new \DateTime('1980-01-01')) {
            $result = false;
        }

        return $result;
    }

    public static function getTimeFormat($php = false)
    {
        $withmeridiam = $php ? static::TIME_FORMAT_SHORT_MERIDIEM_PHP : static::TIME_FORMAT_SHORT_MERIDIEM;
        $noMeridiam = $php ? static::TIME_FORMAT_SHORT_PHP : static::TIME_FORMAT_SHORT;
        return Yii::$app->formatter->isShowMeridiem() ? $withmeridiam : $noMeridiam;
    }

    /**
     * Checks whether the given value is a db date format or not.
     *
     * @param string $value the date value
     * @return boolean
     */
    protected static function isInDbFormat($value)
    {
        return (preg_match(self::REGEX_DBFORMAT_DATE, $value) || preg_match(self::REGEX_DBFORMAT_DATETIME, $value));
    }

    /**
     * @param $value
     * @param $format
     * @return bool|false|int
     */
    public static function parseTime($value, $format)
    {
        $result = false;

        if($format) {
            try {
                $dt = DateTime::createFromFormat(static::parseFormat($format), $value);
                if($dt === false) {
                    return false;
                }
                $result = $dt->getTimestamp() - strtotime('TODAY');
            } catch (\Exception $e) {
                return false;
            }
        }

        if(!$format && !$result) {
            $result = static::parseTime($value, static::TIME_FORMAT_SHORT);
        }

        if(!$format && !$result) {
            $result = static::parseTime($value, static::TIME_FORMAT_SHORT_MERIDIEM);
        }

        return $result;
    }

    /**
     * Checks if the date spans over a whole day if $end is after $start and $start time is 00:00 and $end time is:
     *
     * - 00:00 or 23:59 if $endDateMomentAfter is null (non strict)
     * - 00:00 if $endDateMomentAfter is true
     * - 23:59 if $endDateMomentAfter is false
     *
     * @param $start
     * @param $end
     * @param null $endDateMomentAfter
     * @return bool
     * @throws \Exception
     */
    public static function isAllDay($start, $end, $endDateMomentAfter = null)
    {
        $start = static::getDateTime($start);
        $end = static::getDateTime($end);

        if($start >= $end) {
            return false;
        }

        $startCondition = static::getTimeString($start) === '00:00';

        if(!$startCondition) {
            return false;
        }

        $endTime = static::getTimeString($end);

        if($endDateMomentAfter === null) {
            return $endTime === '00:00' || $endTime === '23:59';
        }

        if($endDateMomentAfter) {
            return $endTime === '00:00';
        }

        return $endTime === '23:59';
    }

    public static function ensureAllDay(DateTime $startDt, DateTime $endDt)
    {
        if($startDt->format('Y-m-d') === $endDt->format('Y-m-d')) {
            $endDt->modify('+1 day');
        }

        $startDt->setTime(0,0,0);
        $endDt->setTime(0,0,0);
    }

    /**
     * @param $date
     * @param string $format
     * @return string
     * @throws \Exception
     */
    public static function getTimeString($date, $format = self::TIME_FORMAT_SHORT)
    {
        return static::getDateTime($date)->format(static::parseFormat($format));
    }

    public static function getDateString($date, $format = self::DATE_FORMAT_SHORT)
    {
        return static::getDateTime($date)->format(static::parseFormat($format));
    }

    /**
     * @param $date
     * @return DateTime|DateTimeInterface
     * @throws \Exception
     */
    public static function getDateTime($date)
    {
        if($date instanceof \DateTimeImmutable) {
            return DateTime::createFromFormat(
                static::DATE_FORMAT_ATOM,
                $date->format(static::DATE_FORMAT_ATOM)
            );
        }

        return $date instanceof DateTime ? clone $date : new DateTime($date);
    }

    /**
     * @param $tz
     * @return DateTimeZone
     */
    public static function getDateTimeZone($tz = null)
    {
        if(!$tz) {
            return null;
        }

        return $tz instanceof DateTimeZone ? $tz : new DateTimeZone($tz);
    }

    public static function getStartTimeZone(CalendarEventIF $event, $asString = false)
    {
        return $asString ? $event->getTimezone() : static::getDateTimeZone($event->getTimezone());
    }

    public static function getEndTimeZone(CalendarEventIF $event, $asString = false)
    {
        $result = $event->getEndTimezone();

        if(!$result) {
            $result = $event->getTimezone();
        }

        return $asString ? $result : static::getDateTimeZone($result);
    }

    public static function cleanRecurrentId($recurrentId, $targetTZ = null)
    {
        $date = ($recurrentId instanceof \DateTimeInterface) ? $recurrentId : new DateTime($recurrentId, new DateTimeZone('UTC'));

        if($targetTZ) {
            $date->setTimezone(new DateTimeZone($targetTZ));
        }

        return $date->format(static::ICAL_TIME_FORMAT);
    }

    public static function flush()
    {
        static::$userTimezoneString = null;
        static::$userTimezone = null;
    }

    /**
     * @return DateTimeZone
     */
    public static function getUserTimeZone($asString = false)
    {
        if(!static::$userTimezone) {
            $tz =  Yii::$app->user->isGuest
                ? Yii::$app->timeZone
                : Yii::$app->user->getTimeZone();

            if(!$tz) {
                $tz = Yii::$app->timeZone;
            }

            if($tz) {
                static::$userTimezoneString = $tz;
                static::$userTimezone = new DateTimeZone($tz);
            }
        }

        return  $asString ? static::$userTimezoneString : static::$userTimezone;
    }

    public static function getSystemTimeZone($asString = false)
    {
        return $asString ? Yii::$app->timeZone : new DateTimeZone(Yii::$app->timeZone);
    }

    public static function toDBDateFormat($date, $fixedTZ = true)
    {
        $date = static::getDateTime($date);

        if(!$fixedTZ) {
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

    public static function translateToUserTimezone($date, $fromTZ = null, $format = self::DB_DATE_FORMAT)
    {
        if(!$fromTZ) {
            $fromTZ = static::getSystemTimeZone();
        }
        return static::translateTimezone($date, $fromTZ, static::getUserTimeZone(), $format);
    }

    public static function translateToSystemTimezone($date, $fromTZ = null, $format = self::DB_DATE_FORMAT)
    {
        if(!$fromTZ) {
            $fromTZ = static::getUserTimeZone();
        }

        return static::translateTimezone($date, $fromTZ, static::getSystemTimeZone(), $format);
    }

    /**
     * @param DateTimeInterface|string $date
     * @param DateTimeZone| string $fromTZ
     * @param DateTimeZone| string $toTZ
     * @param string $format
     * @return DateTime|string
     * @throws \Exception
     */
    public static function translateTimezone($date, $fromTZ, $toTZ, $format = self::DB_DATE_FORMAT)
    {
        $date = static::getDateTime($date);

        $fromTZ = static::getDateTimeZone($fromTZ);
        $toTZ = static::getDateTimeZone($toTZ);

        // Get rid of any old timezone information and set new from timezone, then translate to to timezone
        $date = static::clearTimezone($date, $fromTZ);
        $date->setTimezone($toTZ);

        return $format === false ? $date : $date->format(static::parseFormat($format));
    }

    private static function clearTimezone($date, $newTZ = null)
    {
        if($newTZ) {
            $newTZ = static::getDateTimeZone($newTZ);
        }

        $date = static::getDateTime($date);
        return new DateTime($date->format(self::DB_DATE_FORMAT), $newTZ);
    }

    private static function parseFormat($format = null)
    {
        if (!$format) {
            return null;
        }

        if (strncmp($format, 'php:', 4) === 0) {
            return substr($format, 4);
        }

        return $format;
    }

    public static function generateEventUid(CalendarEventIF $event, $type = 'event') {
        return static::generateUUid($event->getEventType()->getKey());
    }

    public static function generateUUid($type = 'event') {
        return 'humhub-'.$type.'-' . UUIDUtil::getUUID();
    }

    /**
     * @param $model
     * @return CalendarEventIF|null
     */
    public static function getCalendarEvent($model)
    {
        if($model instanceof Content) {
            $model = $model->getModel();
        }

        if($model instanceof CalendarEventIF) {
            return $model;
        }

        if(method_exists($model, 'getCalendarEvent')) {
            $event = $model->getCalendarEvent();
            if($event instanceof CalendarEventIF) {
                return $event;
            }
        }

        return null;
    }

    public static function incrementSequence(CalendarEventIF $entry)
    {
        if($entry instanceof EditableEventIF) {
            $sequence = $entry->getSequence();
            $entry->setSequence( ($sequence === null) ? 0 : ++$sequence);
        }
    }
}
