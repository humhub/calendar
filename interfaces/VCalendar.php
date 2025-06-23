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
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\calendar\Module;
use humhub\modules\topic\models\Topic;
use humhub\modules\user\models\User;
use humhub\modules\content\models\Content;
use yii\base\Model;
use Sabre\VObject;
use yii\helpers\ArrayHelper;
use humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter;


/**
 * Class VCalendar serves as wrapper around sabledavs vobject api.
 *
 */
class VCalendar extends Model
{
    public const PRODID = '-//HumHub Org//HumHub Calendar 0.7//EN';
    public const PARTICIPATION_STATUS_ACCEPTED = 'ACCEPTED';
    public const PARTICIPATION_STATUS_DECLINED = 'DECLINED';
    public const PARTICIPATION_STATUS_TENTATIVE = 'TENTATIVE';

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

    private bool $includeUserInfo;


    /**
     * @param CalendarEventIF|CalendarEventIF[] $items
     * @return VCalendar
     */
    public static function withEvents($items, $tz = null)
    {
        $instance = (new static());
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
        if ($tz && is_string($tz)) {
            $this->vcalendar->add($this->generate_vtimezone($tz));
        }
        return $this;
    }

    public function init()
    {
        parent::init();
        $this->initVObject();
        $this->includeUserInfo = Module::instance()->settings->get('includeUserInfo', false);
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
            $dtStart->setTimezone(CalendarUtils::getStartTimeZone($item));
            $dtEnd->setTimezone(CalendarUtils::getStartTimeZone($item));
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
                    $recurrenceItems = $item->getRecurrenceInstances()->all();
                }
            } elseif (RecurrenceHelper::isRecurrentInstance($item)) {
                $recurrenceId = new DateTime($item->getRecurrenceId());
                $recurrenceId->setTimezone(CalendarUtils::getStartTimeZone($item));
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
            $result['LAST-MODIFIED'] = $lastModified;
        }

        $evt = $this->vcalendar->add('VEVENT', $result);

        if ($isRecurrenceChild) {
            return $this;
        }

        $this->uids[] = $uid;

        if (!empty($recurrenceItems)) {
            foreach ($recurrenceItems as $recurrenceItem) {
                $this->addVEvent($recurrenceItem, true);
            }
        }

        if ($item->isAllDay()) {
            if (isset($evt->DTSTART)) {
                $evt->DTSTART['VALUE'] = 'DATE';
            }

            if (isset($evt->DTEND)) {
                $evt->DTEND['VALUE'] = 'DATE';
            }
        }

        if ($item instanceof CalendarEventParticipationIF) {
            $organizer = $item->getOrganizer();
            if ($organizer instanceof User) {
                $evt->add('ORGANIZER', ['CN' => $this->getCN($organizer)]);
            }

            foreach ($item->findParticipants()->limit(self::MAX_PARTICIPANTS_COUNT)->all() as $user) {
                /* @var $user User */
                $evt->add('ATTENDEE', ['CN' => $this->getCN($user)]);
            }
        }

        $eventType = $item->getEventType();

        if ($eventType instanceof CalendarEntryType && !empty($category = $eventType->name)) {
            $evt->add('CATEGORIES', $category);
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
            foreach (explode(',', $item->getExdate()) as $exdate) {
                $result['EXDATE'][] = $exdate;
            }
        }
    }

    private function getCN(User $user)
    {
        $result = $user->getDisplayName();

        if (!$this->includeUserInfo && $user->email) {
            $result .= ':MAILTO:' . $user->email;
        }

        return $result;
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
