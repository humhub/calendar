<?php


namespace humhub\modules\calendar\interfaces;


use DateTime;
use humhub\modules\calendar\interfaces\CalendarItem;
use yii\base\Model;
use Sabre\VObject;

/**
 * Class VCalendar serves as wrapper around sabledavs vobject api.
 *
 */
class VCalendar extends Model
{
    const PRODID = '-//HumHub Org//HumHub Calendar 0.7//EN';

    /**
     * @var
     */
    public $name;

    public $method = 'PUBLISH';

    /**
     * @var VObject\Component\VCalendar
     */
    private $vcalendar;


    /**
     * @param CalendarItem[] $items
     * @return VCalendar
     */
    public static function withEvents($items, $tz = null)
    {
        $instance = (new static());
        $instance->addTimeZone($tz);

        foreach ($items as $item)
        {
            $instance->addVEvent($item);
        }


        return  $instance;
    }

    public function addTimeZone($tz)
    {
        if($tz && is_string($tz)) {
            $this->vcalendar->add($this->generate_vtimezone($tz));
        }
        return $this;
    }

    public function init()
    {
        parent::init();
        $this->initVObject();
    }


    /**
     * @return VObject\Component\VCalendar
     */
    private function initVObject()
    {
        /**
         * X-WR-CALNAME
         * X-WR-CALDESC
         * X-WR-TIMEZONE
         * X-PUBLISHED-TTL
         */
        $this->vcalendar = new VObject\Component\VCalendar([
            'PRODID' => static::PRODID,
            'METHOD' => $this->method,
        ]);
    }

    public function getInstance()
    {
        return $this->vcalendar;
    }

    public function serialize()
    {
        return $this->vcalendar->serialize();
    }

    /**
     * @param $item CalendarItem
     * @return array []
     * @throws \Exception
     */
    private function addVEvent(CalendarItem $item)
    {
        $dtend = $item->getEndDateTime();

        $result = [
            'DTSTART' => $item->getStartDateTime(),
            'DTEND' => $dtend,
            'SUMMARY' => $item->getTitle()
        ];

        if (property_exists($item, 'location')) {
            $result['LOCATION'] = $item->location;
        }

        if ($item->getRRule()) {
            $result['RRULE'] = $item->getRRule();
        }

        // Note: VObject supports the EXDATE property for exclusions, but not yet the RDATE and EXRULE properties
        if (!empty($item->getExdate())) {
            $result['EXDATE'] = [];
            foreach (explode(',', $item->getExdate()) as $exdate) {
                $result['EXDATE'][] = $exdate;
            }
        }

        if (property_exists($item, 'description')) {
            $result['DESCRIPTION'] = $item->description;
        }

        $evt = $this->vcalendar->add('VEVENT', $result);

        if (!empty($item->getUid())) {
            $evt->UID = $item->getUid();
        }

        if ($item->isAllDay()) {
            if (isset($evt->DTSTART)) {
                $evt->DTSTART['VALUE'] = 'DATE';
            }

            if (isset($evt->DTEND)) {
                $evt->DTEND['VALUE'] = 'DATE';
            }
        }

        return $result;
    }

    /**
     * Returns a VTIMEZONE component for a Olson timezone identifier
     * with daylight transitions covering the given date range.
     *
     * @param string Timezone ID as used in PHP's Date functions
     * @param integer Unix timestamp with first date/time in this timezone
     * @param integer Unix timestap with last date/time in this timezone
     *
     * @return mixed A Sabre\VObject\Component object representing a VTIMEZONE definition
     *               or false if no timezone information is available
     */
    function generate_vtimezone($tzid, $from = 0, $to = 0)
    {
        if (!$from) $from = time();
        if (!$to) $to = $from;
        try {
            $tz = new \DateTimeZone($tzid);
        } catch (\Exception $e) {
            return false;
        }
        // get all transitions for one year back/ahead
        $year = 86400 * 360;
        $transitions = $tz->getTransitions($from - $year, $to + $year);
        $vcalendar = new VObject\Component\VCalendar();
        $vt = $vcalendar->createComponent('VTIMEZONE');
        $vt->TZID = $tz->getName();
        $std = null;
        $dst = null;
        foreach ($transitions as $i => $trans) {
            $cmp = null;
            // skip the first entry...
            if ($i == 0) {
                // ... but remember the offset for the next TZOFFSETFROM value
                $tzfrom = $trans['offset'] / 3600;
                continue;
            }
            // daylight saving time definition
            if ($trans['isdst']) {
                $t_dst = $trans['ts'];
                $dst = $vcalendar->createComponent('DAYLIGHT');
                $cmp = $dst;
            } // standard time definition
            else {
                $t_std = $trans['ts'];
                $std = $vcalendar->createComponent('STANDARD');
                $cmp = $std;
            }
            if ($cmp) {
                $dt = new DateTime($trans['time']);
                $offset = $trans['offset'] / 3600;
                $cmp->DTSTART = $dt->format('Ymd\THis');
                $cmp->TZOFFSETFROM = sprintf('%s%02d%02d', $tzfrom >= 0 ? '+' : '', floor($tzfrom), ($tzfrom - floor($tzfrom)) * 60);
                $cmp->TZOFFSETTO = sprintf('%s%02d%02d', $offset >= 0 ? '+' : '', floor($offset), ($offset - floor($offset)) * 60);
                // add abbreviated timezone name if available
                if (!empty($trans['abbr'])) {
                    $cmp->TZNAME = $trans['abbr'];
                }
                $tzfrom = $offset;
                $vt->add($cmp);
            }
            // we covered the entire date range
            if ($std && $dst && min($t_std, $t_dst) < $from && max($t_std, $t_dst) > $to) {
                break;
            }
        }
        // add X-MICROSOFT-CDO-TZID if available
        $microsoftExchangeMap = array_flip(VObject\TimeZoneUtil::$microsoftExchangeMap);
        if (array_key_exists($tz->getName(), $microsoftExchangeMap)) {
            $vt->add('X-MICROSOFT-CDO-TZID', $microsoftExchangeMap[$tz->getName()]);
        }
        return $vt;
    }


    /**
     * @param $items CalendarItem|CalendarItem[]|array
     * @return VCalendar
     * @throws \Exception
     */
    public function add($items)
    {
        if (!is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $this->addVEvent($item);
        }

        return $this;
    }
}