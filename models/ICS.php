<?php

/**
 *Original script: https://gist.github.com/jakebellacera/635416
 */

namespace humhub\modules\calendar\models;

class ICS
{
    const DT_FORMAT = 'Ymd\THis';
    protected $summary;
    protected $description;
    protected $dtstart;
    protected $dtend;
    protected $location;
    protected $url;
    protected $timezone;

    public function __construct($summary, $description, $dtstart, $dtend, $location, $url, $timezone)
    {
        $this->summary = $this->escapeString($summary);
        $this->description = $this->escapeString($description);
        $this->dtstart = $this->formatTimestamp($dtstart);
        $this->dtend = $this->formatTimestamp($dtend);
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
        $ics_props = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'LOCATION:' . $this->location,
            'DESCRIPTION:' . $this->description,
            'DTSTART;TZID=' . $this->timezone . ':' . $this->dtstart,
            'DTEND;TZID=' . $this->timezone . ':' . $this->dtend,
            'SUMMARY:' . $this->summary,
            'URL:' . $this->url,
            'DTSTAMP:' . $this->formatTimestamp('now'),
            'UID:' . uniqid(),
            'END:VEVENT',
            'END:VCALENDAR'
        );
        return $ics_props;
    }

    private function formatTimestamp($timestamp)
    {
        $dt = new \DateTime($timestamp);
        return $dt->format(self::DT_FORMAT);
    }

    private function escapeString($str)
    {
        return preg_replace('/([\,;])/','\\\$1', $str);
    }
}
