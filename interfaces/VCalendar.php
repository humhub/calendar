<?php


namespace humhub\modules\calendar\interfaces;


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
            'SUMMARY' =>$item->getTitle()
        ];

        if(property_exists($item, 'location')) {
            $result['LOCATION'] = $item->location;
        }

       if($item->getRRule()) {
           $result['RRULE'] = $item->getRRule();
       }

       // Note: VObject supports the EXDATE property for exclusions, but not yet the RDATE and EXRULE properties
       if(!empty($item->getExdate())) {
           $result['EXDATE'] = [];
           foreach(explode(',', $item->getExdate()) as $exdate) {
               $result['EXDATE'][] = $exdate;
           }
       }

        if(property_exists($item, 'description')) {
            $result['DESCRIPTION'] = $item->description;
        }

        $evt = $this->vcalendar->add('VEVENT', $result);

        if(method_exists($item, 'getUid')&& !empty($item->getUid())) {
            $evt->UID = $item->getUid();
        }

        if($item->isAllDay()) {
            if(isset($evt->DTSTART)) {
                $evt->DTSTART['VALUE'] = 'DATE';
            }

            if(isset($evt->DTEND)) {
                $evt->DTEND['VALUE'] = 'DATE';
            }
        }

        return $result;
    }

    /**
     * @param $items CalendarItem|CalendarItem[]|array
     * @return VCalendar
     * @throws \Exception
     */
    public function add($items)
    {
        if(!is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $this->addVEvent($item);
        }

        return $this;
    }
}