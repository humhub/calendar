<?php

/**
 *Original script: https://gist.github.com/jakebellacera/635416
 */

namespace humhub\modules\calendar\models;

use DateInterval;
use DateTime;
use Yii;

class ICS
{
    const DT_FORMAT_TIME = 'php:His';
    const DT_FORMAT_DAY = 'php:Ymd';

    protected $summary;
    protected $description;
    protected $dtstart;
    protected $dtend;
    protected $location;
    protected $url;
    protected $timezone;

    /**
     * ICS constructor.
     * @param string $summary
     * @param string $description
     * @param string $dtstart
     * @param string $dtend
     * @param string $location
     * @param string $url
     * @param string $timezone
     * @param bool $allDay
     */
    public function __construct($summary, $description, $dtstart, $dtend, $location, $url, $timezone, $allDay = false)
    {
        if($allDay) {
            $dtend = (new DateTime($dtend))->add(new DateInterval('P1D'));
        }

        $this->summary = $this->escapeString($summary);
        $this->description = $this->escapeString($description);
        $this->dtstart = $this->formatTimestamp($dtstart, $allDay);
        $this->dtend = $this->formatTimestamp($dtend, $allDay);
        $this->location = $this->escapeString($location);
        $this->url = $this->escapeString($url);
        $this->timezone = $timezone;
    }

    public function __toString()
    {
        $rows = $this->buildProps();
        $string =  implode("\r\n", $rows);
        return $string;
    }

    private function buildProps()
    {
        $ics_props = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'LOCATION:' . $this->location,
            'DESCRIPTION:' . $this->description,
            'DTSTART:' . $this->dtstart,
            'DTEND:' . $this->dtend,
            'SUMMARY:' . $this->summary,
            'URL:' . $this->url,
            'DTSTAMP:' . $this->formatTimestamp('now'),
            'UID:' . uniqid(),
            'END:VEVENT',
            'END:VCALENDAR'
        ];
        return $ics_props;
    }

    private function formatTimestamp($timestamp, $allDay = false)
    {
        $dt = ($timestamp instanceof DateTime) ? $timestamp : new DateTime($timestamp);
        $result =  Yii::$app->formatter->asDate($dt, self::DT_FORMAT_DAY);

        if(!$allDay) {
            $result .= "T".  Yii::$app->formatter->asTime($dt, self::DT_FORMAT_TIME);
        }

        return $result;
    }

    private function escapeString($str)
    {
        return preg_replace('/([\,;])/','\\\$1', $str);
    }
}
