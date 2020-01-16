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
 *
 * @todo change base class back to BaseObject after v1.3 is stable
 */

namespace humhub\modules\calendar\models;

use humhub\modules\calendar\helpers\CalendarUtils;
use Yii;
use DateTime;
use humhub\libs\TimezoneHelper;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use yii\base\Component;

class CalendarDateFormatter extends Component
{

    /**
     * @var CalendarEventIF
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

    public function getFormattedStartDate($format = 'long')
    {
        return static::formatDate($this->calendarItem->getStartDateTime(), $format, $this->calendarItem->isAllDay());
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

    public function getFormattedEndDate($format = 'long')
    {
        $endDate = $this->calendarItem->getEndDateTime();
        if($this->calendarItem->isAllDay()) {
            $endDate->modify('-1 day');
        }
        return static::formatDate($endDate, $format, $this->calendarItem->isAllDay());
    }

    /**
     * @param \DateTimeInterface $date
     * @param string $format
     * @param bool $allDay
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function formatDate(\DateTimeInterface $date, $format = 'long', $allDay = false)
    {
        if($allDay) {
            Yii::$app->formatter->timeZone = $date->getTimezone()->getName();
        }

        $result = Yii::$app->formatter->asDate($date, $format);

        if($allDay) {
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
        if(!$this->isSameDay()) {
            $result .= ', '.$this->getFormattedStartTime().  ' - ';
            $result .= $this->getFormattedEndDate($format).', '.$this->getFormattedEndTime();
        } else {
            $result .= ' ('.$this->getFormattedStartTime().' - '.$this->getFormattedEndTime().')';
        }

        return $result;
    }

    protected function isSameDay()
    {
        $start =  $this->calendarItem->getStartDateTime();
        $end = $this->calendarItem->getEndDateTime();

        if(!$this->calendarItem->isAllDay()) {
            $start->setTimezone(CalendarUtils::getUserTimeZone());
            $end->setTimezone(CalendarUtils::getUserTimeZone());
        }
        
        return $start->format('Y-m-d')
            === $end->format('Y-m-d');
    }

    protected  function getFormattedAllDay($format = 'long')
    {
        $result = $this->getFormattedStartDate($format);

        if($this->getDurationDays() > 1) {
            $result .= ' - ' . $this->getFormattedEndDate($format);
        }

        return $result;
    }

    public function getDurationDays()
    {
        $end = $this->calendarItem->getEndDateTime();
        $interval = $this->calendarItem->getStartDateTime()->diff($end, true);
        return $interval->days;
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