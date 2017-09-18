<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 17.09.2017
 * Time: 21:21
 */

namespace humhub\modules\calendar\models;


use Yii;
use DateTime;
use humhub\libs\TimezoneHelper;
use humhub\modules\calendar\interfaces\CalendarItem;
use yii\base\Object;

class CalendarDateFormatter extends Object
{

    /**
     * @var CalendarItem
     */
    public $calendarItem;

    public function getFormattedTime($format = 'long')
    {
        if($this->calendarItem->isAllDay()) {
            return $this->getFormattedAllDay($format);
        } else {
            return $this->getFormattedNonAlLDay($format);
        }
    }

    public function getFormattedStartDate($format = 'long', $timeZone = null)
    {
        if($timeZone) {
            Yii::$app->formatter->timeZone = $timeZone;
        }

        $result = Yii::$app->formatter->asDate($this->calendarItem->getStartDateTime(), $format);

        if($timeZone) {
            Yii::$app->i18n->autosetLocale();
        }

        return $result;
    }

    public function getFormattedStartTime($format = 'short', $timeZone = null)
    {
        if($timeZone) {
            Yii::$app->formatter->timeZone = $timeZone;
        }

        $result = Yii::$app->formatter->asTime($this->calendarItem->getStartDateTime(), $format);

        if($timeZone) {
            Yii::$app->i18n->autosetLocale();
        }

        return $result;
    }

    public function getFormattedEndDate($format = 'long', $timeZone = null)
    {
        if($timeZone) {
            Yii::$app->formatter->timeZone = $timeZone;
        }

        $result = Yii::$app->formatter->asDate($this->calendarItem->getEndDateTime(), $format);

        if($timeZone) {
            Yii::$app->i18n->autosetLocale();
        }

        return $result;
    }

    public function getFormattedEndTime($format = 'short', $timeZone = null)
    {
        if($timeZone) {
            Yii::$app->formatter->timeZone = $timeZone;
        }

        $result = Yii::$app->formatter->asTime($this->calendarItem->getEndDateTime(), $format);

        if($timeZone) {
            Yii::$app->i18n->autosetLocale();
        }

        return $result;
    }

    protected  function getFormattedNonAllDay($format = 'long')
    {
        $result = $this->getFormattedStartDate($format);
        if($this->getDurationDays() > 1) {
            $result .= ', '.$this->getFormattedStartTime().  ' - ';
            $result .= $this->getFormattedEndDate($format).', '.$this->getFormattedEndTime();
        } else {
            $result .= ' ('.$this->getFormattedStartTime().' - '.$this->getFormattedEndTime().')';
        }

        return $result;
    }

    protected  function getFormattedAllDay($format = 'long')
    {
        $userTimeZone = Yii::$app->formatter->timeZone;
        $resultTimeZone = empty($this->calendarItem->getTimezone()) ? Yii::$app->timeZone : $this->calendarItem->getTimezone();
        $result = $result = $this->getFormattedStartDate($format, $resultTimeZone);

        if($this->getDurationDays() > 1) {
            $result .= ' - ' . $this->getFormattedEndDate($format, $resultTimeZone);
        }

        if($resultTimeZone !== $userTimeZone) {
            $result .= ' ('.  self::getTimezoneLabel($resultTimeZone) .')';
        }

        return $result;
    }

    public function getDurationDays()
    {
        $interval = $this->calendarItem->getStartDateTime()->diff($this->calendarItem->getEndDateTime(), true);
        return $interval->days + 1;
    }

    /**
     * Checks if the event is currently running.
     */
    public function isRunning()
    {
        $s = $this->calendarItem->getStartDateTime();
        $e = $this->calendarItem->getEndDateTime();

        $now = new DateTime();

        return $now >= $s && $now <= $e;
    }

    public function getOffsetDays()
    {
        $s = new DateTime($this->calendarItem->getStartDateTime());
        return $s->diff(new DateTime)->days;
    }

    public static function getTimezoneLabel($timeZone)
    {
        $entries = static::getTimeZoneItems();
        return isset($entries[$timeZone]) ? $entries[$timeZone] : $timeZone;
    }

    public static function getTimeZoneItems()
    {
        static $timeZoneItems = null;

        if(empty($timeZoneItems)) {
            $timeZoneItems = TimezoneHelper::generateList();
        }

        return $timeZoneItems;
    }

}