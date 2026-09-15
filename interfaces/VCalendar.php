<?php

namespace humhub\modules\calendar\interfaces;

use DateTime;
use DateTimeZone;
use Exception;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\dav\EventSync;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\event\legacy\CalendarEventIFWrapper;
use humhub\modules\calendar\interfaces\participation\CalendarEventParticipationIF;
use humhub\modules\calendar\interfaces\recurrence\RecurrentEventIF;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\calendar\Module;
use humhub\modules\topic\models\Topic;
use humhub\modules\user\models\User;
use humhub\modules\content\models\Content;
use Yii;
use yii\base\Model;
use Sabre\VObject;
use yii\helpers\ArrayHelper;
use yii\helpers\Url as YiiUrl;
use humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter;

/**
 * Class VCalendar serves as wrapper around sabledavs vobject api.
 *
 */
class VCalendar extends Model
{
    public const PRODID = '-//HumHub Org//HumHub Calendar 0.7//EN';

    public const MAX_PARTICIPANTS_COUNT = 200;

    /**
     * @var
     */
    public $name;

    public $method = 'PUBLISH';

    /**
     * @var VObject\Component\VCalendar
     */
    private $vcalendar;

    private bool $includeParticipantInfo;

    private bool $includeParticipantEmail;

    /**
     * TZIDs for which a VTIMEZONE component has already been added to the calendar.
     * @var string[]
     */
    private array $declaredTimezones = [];


    /**
     * @param CalendarEventIF|CalendarEventIF[] $items
     * @return VCalendar
     */
    public static function withEvents($items, $tz = null, $calendarName = null)
    {
        $instance = (new static(['name' => $calendarName]));
        $instance->addTimeZone($tz);

        if (!is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $item) {
            if (is_array($item)) {
                $item = new CalendarEventIFWrapper(['options' => $item]);
            }
            $instance->addVEvent($item);
        }


        return  $instance;
    }

    public function addTimeZone($tz)
    {
        $this->ensureTimeZone($tz);
        return $this;
    }

    /**
     * Ensures a VTIMEZONE component for the given Olson timezone identifier is present in
     * the calendar. Safe to call repeatedly (including with the same, or no, timezone):
     * each TZID is only ever declared once, and UTC/GMT/floating times never need a
     * VTIMEZONE component at all (RFC 5545 3.6.5).
     *
     * Every DTSTART/DTEND/RECURRENCE-ID we emit with a TZID parameter must have a matching
     * VTIMEZONE somewhere in the file, or strict iCalendar consumers (notably Google
     * Calendar's importer) reject the whole file instead of just skipping the bad event.
     *
     * @param string|DateTimeZone|null $tz
     */
    private function ensureTimeZone($tz): void
    {
        if (!$tz) {
            return;
        }

        $tzid = $tz instanceof DateTimeZone ? $tz->getName() : (string) $tz;

        if ($tzid === '' || in_array($tzid, ['UTC', 'GMT', 'Z', '+00:00'], true)) {
            return;
        }

        if (in_array($tzid, $this->declaredTimezones, true)) {
            return;
        }

        $this->declaredTimezones[] = $tzid;

        $vt = $this->generate_vtimezone($tzid);
        if ($vt) {
            $this->vcalendar->add($vt);
        }
    }

    public function init()
    {
        parent::init();
        $this->initVObject();
        $this->includeParticipantInfo = Module::instance()->settings->get('includeParticipantInfo', false);
        $this->includeParticipantEmail = Module::instance()->settings->get('includeParticipantEmail', false);
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


        $params = [
            'PRODID' => static::PRODID,
            'METHOD' => $this->method,
        ];

        if ($this->name !== false) {
            $params['X-WR-CALNAME'] = trim(Yii::$app->name . ' - ' . $this->name, " \n\r\t\v\0-");
        }

        $this->vcalendar = new VObject\Component\VCalendar($params);
    }

    public function getInstance()
    {
        return $this->vcalendar;
    }

    public function serialize()
    {
        return $this->vcalendar->serialize();
    }

    private $uids = [];

    /**
     * @param CalendarEventIF $item
     * @param bool $isRecurrenceChild
     * @param bool $initRecurrenceChildren
     * @return static
     * @throws Exception
     */
    private function addVEvent(CalendarEventIF $item, bool $isRecurrenceChild = false, bool $initRecurrenceChildren = true)
    {
        $dtend = clone $item->getEndDateTime();

        if (!$isRecurrenceChild) {
            $uid = $item->getUid();
            if (!$uid || in_array($uid, $this->uids)) {
                return $this;
            }
        }

        if ($item->isAllDay() && $dtend->format('H:i') === '23:59') {
            // Translate for legacy events
            $dtend->modify('+1 hour')->setTime(0, 0, 0);
        }

        $dtStart = clone $item->getStartDateTime();
        $dtEnd =  clone $item->getEndDateTime();

        if (!$item->isAllDay()) {
            $eventTimeZone = CalendarUtils::getStartTimeZone($item);
            $dtStart->setTimezone($eventTimeZone);
            $dtEnd->setTimezone($eventTimeZone);
            $this->ensureTimeZone($eventTimeZone);
        } elseif ($dtEnd <= $dtStart) {
            $dtEnd = (clone $dtStart)->modify('+1 day');
        }

        $result = [
            'UID' => $item->getUid(),
            'DTSTART' => $dtStart,
            'DTEND' => $dtEnd,
            'SUMMARY' => $item->getTitle(),
        ];

        if (isset($item->closed) && $item->closed) {
            $result['STATUS'] = 'CANCELLED';
        }

        if (!empty($item->getLocation())) {
            $result['LOCATION'] = $item->getLocation();
        }

        if (!empty($item->getDescription())) {
            $result['DESCRIPTION'] = RichTextToPlainTextConverter::process($item->getDescription());
        }

        $eventUrl = $item->getUrl();
        if (!empty($eventUrl)) {
            $result['URL'] = YiiUrl::to($eventUrl, true);
        }

        if (isset($item->content->visibility)) {
            $result['CLASS'] = ArrayHelper::getValue([
                Content::VISIBILITY_PRIVATE => 'PRIVATE',
                Content::VISIBILITY_PUBLIC => 'PUBLIC',
                Content::VISIBILITY_OWNER => 'CONFIDENTIAL',
            ], $item->content->visibility);
        }

        if ($item instanceof RecurrentEventIF && RecurrenceHelper::isRecurrent($item)) {
            if (RecurrenceHelper::isRecurrentRoot($item)) {
                $result['RRULE'] = $item->getRRule();

                // Note: VObject supports the EXDATE property for exclusions, but not yet the RDATE and EXRULE properties
                if (!empty($item->getExdate())) {
                    $result['EXDATE'] = [];
                    foreach (explode(',', $item->getExdate()) as $exdate) {
                        $result['EXDATE'][] = $exdate;
                    }
                }

                if ($initRecurrenceChildren) {
                    $recurrenceItems = $item->getRecurrenceQuery()->getRecurrenceExceptions();
                }
            } elseif (RecurrenceHelper::isRecurrentInstance($item)) {
                $recurrenceId = $item->isAllDay()
                    ? clone $dtStart
                    : new DateTime($item->getRecurrenceId());

                if (!$item->isAllDay()) {
                    $recurrenceTimeZone = CalendarUtils::getStartTimeZone($item);
                    $recurrenceId->setTimezone($recurrenceTimeZone);
                    $this->ensureTimeZone($recurrenceTimeZone);
                }

                $result['RECURRENCE-ID'] = $recurrenceId;
            }
        } else {
            $this->setLegacyRecurrentData($item, $result);
        }

        if ($item->getSequence() !== null) {
            $result['SEQUENCE'] = $item->getSequence();
        }

        $lastModified = $item->getLastModified();
        if ($lastModified) {
            $result['LAST-MODIFIED'] = $lastModified->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        }

        $evt = $this->vcalendar->add('VEVENT', $result);

        if ($item->isAllDay()) {
            if (isset($evt->DTSTART)) {
                $evt->DTSTART['VALUE'] = 'DATE';
            }

            if (isset($evt->DTEND)) {
                $evt->DTEND['VALUE'] = 'DATE';
            }

            if (isset($evt->{'RECURRENCE-ID'})) {
                $evt->{'RECURRENCE-ID'}['VALUE'] = 'DATE';
            }
        }

        if ($isRecurrenceChild) {
            return $this;
        }

        $this->uids[] = $uid;

        if (!empty($recurrenceItems)) {
            foreach ($recurrenceItems as $recurrenceItem) {
                $this->addVEvent($recurrenceItem, true);
            }
        }

        $eventType = $item->getEventType();

        if ($eventType instanceof CalendarEntryType && !empty($category = $eventType->name)) {
            $evt->add('CATEGORIES', $category);
        }

        if ($item instanceof CalendarEventParticipationIF) {
            $organizer = $item->getOrganizer();
            if ($organizer instanceof User) {
                $evt->add(
                    'ORGANIZER;CN=' . $this->getCN($organizer),
                    'mailto:' . $this->getMailto($organizer),
                );
            }

            if ($this->includeParticipantInfo) {
                $participationStateMap = array_flip(EventSync::PARTICIPATION_STATE_MAP);

                foreach ($item->getParticipantEntries()->with('user')->all() as $participant) {
                    /* @var $user User */
                    $evt->add(
                        'ATTENDEE;CN=' . $this->getCN($participant->user) . ';PARTSTAT=' . ArrayHelper::getValue($participationStateMap, $participant->participation_state),
                        'mailto:' . $this->getMailto($participant->user),
                    );
                }
            }
        }

        return $this;
    }

    private function setLegacyRecurrentData($item, &$result)
    {
        if (!$item instanceof CalendarEventIFWrapper) {
            return;
        }

        if ($item->getRRule()) {
            $result['RRULE'] = $item->getRRule();
        }

        // Note: VObject supports the EXDATE property for exclusions, but not yet the RDATE and EXRULE properties
        if (!empty($item->getExdate())) {
            $result['EXDATE'] = [];
            foreach (explode(',', (string) $item->getExdate()) as $exdate) {
                $result['EXDATE'][] = $exdate;
            }
        }
    }

    private function getCN(User $user)
    {
        return addslashes($user->getDisplayName());
    }

    private function getMailto(User $user)
    {
        if ($this->includeParticipantEmail && $user->email) {
            return $user->email;
        }

        return '-';
    }

    /**
     * Formats a UTC offset given in (possibly fractional, e.g. 5.5 for +05:30) hours as an
     * iCalendar UTC-OFFSET value (e.g. "+0530", "-0400"), correctly zero-padded for
     * negative and fractional offsets alike.
     */
    private static function formatTzOffset(float $hours): string
    {
        $sign = $hours >= 0 ? '+' : '-';
        $absHours = abs($hours);
        $h = (int) floor($absHours);
        $m = (int) round(($absHours - $h) * 60);
        return sprintf('%s%02d%02d', $sign, $h, $m);
    }

    /**
     * Returns a VTIMEZONE component for a Olson timezone identifier
     * with daylight transitions covering the given date range.
     *
     * @param string Timezone ID as used in PHP's Date functions
     * @param int Unix timestamp with first date/time in this timezone
     * @param int Unix timestap with last date/time in this timezone
     *
     * @return mixed A Sabre\VObject\Component object representing a VTIMEZONE definition
     *               or false if no timezone information is available
     * @throws Exception
     */
    public function generate_vtimezone($tzid, $from = 0, $to = 0)
    {
        if (!$from) {
            $from = time();
        }
        if (!$to) {
            $to = $from;
        }
        try {
            $tz = new \DateTimeZone($tzid);
        } catch (Exception) {
            return false;
        }
        // get all transitions for one year back/ahead
        $year = 86400 * 360;
        $transitions = $tz->getTransitions($from - $year, $to + $year);
        $vcalendar = new VObject\Component\VCalendar();
        $vt = $vcalendar->createComponent('VTIMEZONE');
        $vt->TZID = $tz->getName();

        $offset = $transitions[0]['offset'] ?? 0;
        $tzname = $transitions[0]['abbr'] ?? $tzid;

        $offsetStr = static::formatTzOffset($offset / 3600);

        $standard = $vcalendar->createComponent('STANDARD');
        $standard->add('DTSTART', '19700101T000000');
        $standard->add('TZOFFSETFROM', $offsetStr);
        $standard->add('TZOFFSETTO', $offsetStr);
        $standard->add('TZNAME', $tzname);
        $vt->add($standard);

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
                $cmp->TZOFFSETFROM = static::formatTzOffset($tzfrom);
                $cmp->TZOFFSETTO = static::formatTzOffset($offset);
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
     * @param $initRecurrenceChildren bool
     * @return VCalendar
     * @throws Exception
     */
    public function add($items, bool $initRecurrenceChildren = true)
    {
        if (!is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $this->addVEvent($item, false, $initRecurrenceChildren);
        }

        return $this;
    }
}
