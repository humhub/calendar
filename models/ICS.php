<?php

/**
* https://gist.github.com/jakebellacera/635416
 * ICS.php
 * =======
 * Use this class to create an .ics file.
 *
 * Usage
 * -----
 * Basic usage - generate ics file contents (see below for available properties):
 *   $ics = new ICS($props);
 *   $ics_file_contents = $ics->to_string();
 *
 * Setting properties after instantiation
 *   $ics = new ICS();
 *   $ics->set('summary', 'My awesome event');
 *
 * You can also set multiple properties at the same time by using an array:
 *   $ics->set(array(
 *     'dtstart' => 'now + 30 minutes',
 *     'dtend' => 'now + 1 hour'
 *   ));
 *
 * Available properties
 * --------------------
 * description
 *   String description of the event.
 * dtend
 *   A date/time stamp designating the end of the event. You can use either a
 *   DateTime object or a PHP datetime format string (e.g. "now + 1 hour").
 * dtstart
 *   A date/time stamp designating the start of the event. You can use either a
 *   DateTime object or a PHP datetime format string (e.g. "now + 1 hour").
 * location
 *   String address or description of the location of the event.
 * summary
 *   String short summary of the event - usually used as the title.
 * url
 *   A url to attach to the the event. Make sure to add the protocol (http://
 *   or https://).
 */

namespace humhub\modules\calendar\models;

class ICS {
  const DT_FORMAT = 'Ymd\THis';
  protected $summary;
  protected $description;
  protected $dtstart;
  protected $dtend;
  protected $location;
  protected $url;
  protected $timezone;

  public function __construct($summary, $description, $dtstart, $dtend, $location, $url, $timezone) {
    $this->summary = $this->escape_string($summary);
    $this->description = $this->escape_string($description);
    $this->dtstart = $this->format_timestamp($dtstart);
    $this->dtend = $this->format_timestamp($dtend);
    $this->location = $this->escape_string($location);
    $this->url = $this->escape_string($url);
    $this->timezone = $timezone;
  }

  public function to_string() {
    $rows = $this->build_props();
    return implode("\r\n", $rows);
  }

  private function build_props() {

    // Build ICS properties - add header
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
      'DTSTAMP:' . $this->format_timestamp('now'),
      'UID:' . uniqid(),
      'END:VEVENT',
      'END:VCALENDAR'
    );
    return $ics_props;
  }

  private function format_timestamp($timestamp) {
    $dt = new \DateTime($timestamp);
    return $dt->format(self::DT_FORMAT);
  }

  private function escape_string($str) {
    return preg_replace('/([\,;])/','\\\$1', $str);
  }
}
