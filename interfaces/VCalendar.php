<?php


namespace humhub\modules\calendar\interfaces;


use DateTime;
use Exception;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\event\legacy\CalendarEventIFWrapper;
use humhub\modules\calendar\interfaces\participation\CalendarEventParticipationIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use humhub\modules\user\models\User;
use yii\base\Model;
use Sabre\VObject;

/**
 * Class VCalendar serves as wrapper around sabledavs vobject api.
 *
 */
class VCalendar extends Model
{
    const PRODID = '-//HumHub Org//HumHub Calendar 0.7//EN';
    const PARTICIPATION_STATUS_ACCEPTED = 'ACCEPTED';
    const PARTICIPATION_STATUS_DECLINED = 'DECLINED';
    const PARTICIPATION_STATUS_TENTATIVE = 'TENTATIVE';

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
     * @param CalendarEventIF[] $items
     * @return VCalendar
     */
    public static function withEvents($items, $tz = null)
    {
        $instance = (new static());
        $instance->addTimeZone($tz);

        if(!is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $item)
        {
            if(is_array($item)) {
                $item = new CalendarEventIFWrapper(['options' => $item]);
            }
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
     * @return void
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
     * @param $item CalendarEventIF
     * @return static
     * @throws Exception
     */
    private function addVEvent(CalendarEventIF $item)
    {
        $dtend = clone $item->getEndDateTime();

        if($item->isAllDay()) {
            // Translate for legacy events
            if($dtend->format('H:i') === '23:59') {
                $dtend->modify('+1 hour')->setTime(0,0,0);
            }
        }

        $dtStart = clone $item->getStartDateTime();
        $dtEnd =  clone $item->getEndDateTime();

        if(!$item->isAllDay()) {
            $dtStart->setTimezone(CalendarUtils::getStartTimeZone($item));
            $dtEnd->setTimezone(CalendarUtils::getStartTimeZone($item));
        }

        $result = [
            'UID' => $item->getUid(),
            'DTSTART' => $dtStart,
            'DTEND' => $dtEnd,
            'SUMMARY' => $item->getTitle(),
        ];

        if(!empty($item->getLocation())) {
            $result['LOCATION'] = $item->getLocation();
        }

        if(!empty($item->getDescription())) {
            $result['DESCRIPTION'] = $item->getDescription();
        }

        if ($item instanceof RecurrentEventIF && RecurrenceHelper::isRecurrent($item)) {

            if(RecurrenceHelper::isRecurrentRoot($item)) {
                $result['RRULE'] = $item->getRRule();

                // Note: VObject supports the EXDATE property for exclusions, but not yet the RDATE and EXRULE properties
                if (!empty($item->getExdate())) {
                    $result['EXDATE'] = [];
                    foreach (explode(',', $item->getExdate()) as $exdate) {
                        $result['EXDATE'][] = $exdate;
                    }
                }
            } else if(RecurrenceHelper::isRecurrentInstance($item)) {
                $result['RECURRENCE-ID'] = $item->getRecurrenceId();
            }

        } else {
            $this->setLegacyRecurrentData($item, $result);

        }

        if ($item->getSequence() !== null) {
            $result['SEQUENCE'] = $item->getSequence();
        }

        $lastModified = $item->getLastModified();
        if($lastModified) {
            $result['LAST-MODIFIED'] = $lastModified;
        }

        $evt = $this->vcalendar->add('VEVENT', $result);

        if ($item->isAllDay()) {
            if (isset($evt->DTSTART)) {
                $evt->DTSTART['VALUE'] = 'DATE';
            }

            if (isset($evt->DTEND)) {
                $evt->DTEND['VALUE'] = 'DATE';
            }
        }

        if($item instanceof CalendarEventParticipationIF) {
            $organizer = $item->getOrganizer();
            if($organizer instanceof User) {
                $evt->add('ORGANIZER', ['CN' => $this->getCN($organizer)]);
            }

            /** This should be configurable because its may not be desired.
            foreach ($item->findParticipants([CalendarEventParticipationIF::PARTICIPATION_STATUS_ACCEPTED])->limit(20)->all() as $user) {
                /* @var $user User
                $evt->add('ATTENDEE', $this->getCN($user));
            }

            if(!empty($item->getExternalParticipants())) {
                foreach ($item->getExternalParticipants() as $email) {
                    $evt->add('ATTENDEE', 'MAILTO:'.$email);
                }
            }
            **/
        }

        return $this;
    }

    private function setLegacyRecurrentData($item, &$result)
    {
        if(!$item instanceof CalendarEventIFWrapper) {
            return;
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
    }

    private function getCN(User $user)
    {
        $result = $user->getDisplayName();

        if($user->email) {
            $result .= ':MAILTO:'.$user->email;
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
     * @throws Exception
     */
    function generate_vtimezone($tzid, $from = 0, $to = 0)
    {
        if (!$from) $from = time();
        if (!$to) $to = $from;
        try {
            $tz = new \DateTimeZone($tzid);
        } catch (Exception $e) {
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
     * @param $items CalendarEventIF|CalendarEventIF[]|array
     * @return VCalendar
     * @throws Exception
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