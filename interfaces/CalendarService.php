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
 * Date: 14.09.2017
 * Time: 12:33
 */

namespace humhub\modules\calendar\interfaces;

use DateInterval;
use DateTime;
use humhub\modules\calendar\CalendarUtils;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * This service component supports integration functionality and is responsible for retrieving
 * calendar related data from other modules using the calendar interface.
 *
 * You can receive an instance of this component by calling
 *
 * ```php
 * $calendarService = Yii::$app->getModule('calendar')->get(CalendarService::class);
 * ```
 * @package humhub\modules\calendar\interfaces
 */
class CalendarService extends Component
{
    /**
     * Used for assembling all available item types provided by other modules
     */
    const EVENT_GET_ITEM_TYPES = 'getItemTypes';

    /**
     * Used for assembling all calendar items of other modules
     */
    const EVENT_FIND_ITEMS = 'findItems';

    private static $resultCache = [];

    /**
     * Searches for integrated calendars of other modules.
     * Modules can append their event types by calling CalendarItemTypeEvent::addType() and providing
     * a unique key and options as for example:
     *
     * ```php
     * $event->addType('myType', [
     *      'title' => Yii::t('MyModule.base', 'My Type),
     *      'color' => '#2c99d6' // default color for this even type
     * ]);
     * ```
     *
     * The module has to get sure it only returns results either if $contentContainer is not provided or the given
     * module is enabled for the given ContentContainerActiveRecord.
     *
     * @param null ContentContainerActiveRecord $contentContainer
     * @return array|null
     */
    public function getCalendarItemTypes(ContentContainerActiveRecord $contentContainer = null)
    {
        $containerKey = ($contentContainer) ? $contentContainer->contentcontainer_id : 'global';

        if(isset(static::$resultCache[$containerKey])) {
            return static::$resultCache[$containerKey];
        }

        $event = new CalendarItemTypesEvent(['contentContainer' => $contentContainer]);
        $this->trigger(self::EVENT_GET_ITEM_TYPES, $event);

        $result = [];
        foreach ($event->getTypes() as $key => $options) {
            $result[] = new CalendarItemType(['key' => $key, 'options' => $options, 'contentContainer' => $contentContainer]);
        }


        return static::$resultCache[$containerKey] = $result;
    }

    public static function flushCache() {
        static::$resultCache = [];
    }


    /**
     * Merges all CalendarItems for a given ContentContainerActiveRecord filtered by $from, $to and $filters.
     *
     * CalendarItems can either be CalendarEntries or CalendarItems provided
     *
     * @param DateTime $start
     * @param DateTime $end
     * @param array $filters
     * @param ContentContainerActiveRecord $contentContainer
     * @param null $limit
     * @param bool $expand
     * @return CalendarItem[]
     * @throws \Throwable
     */
    public function getCalendarItems(DateTime $start = null, DateTime $end = null, $filters = [], ContentContainerActiveRecord $contentContainer = null, $limit = null, $expand = true)
    {
        $result = [];

        $event = new CalendarItemsEvent(['contentContainer' => $contentContainer, 'start' => $start, 'end' => $end, 'filters' => $filters, 'limit' => $limit, 'expand' => $expand]);
        $this->trigger(static::EVENT_FIND_ITEMS, $event);

        foreach($event->getItems() as $itemTypeKey => $items) {
            $itemType = $this->getItemType($itemTypeKey, $contentContainer);

            if($itemType && $itemType->isEnabled()) {
                foreach ($items as $item) {
                    $result[] = new CalendarItemWrapper(['itemType' => $itemType, 'options' => $item]);
                }
            }
        }

        $calendarEntries = CalendarEntryQuery::findForFilter($start, $end, $contentContainer, $filters, $limit, $expand);

        $result = array_merge($calendarEntries, $result);

        ArrayHelper::multisort($result, ['startDateTime', 'endDateTime'], [SORT_ASC, SORT_ASC]);

        return (count($result) > $limit) ? array_slice($result, 0, $limit) : $result;
    }

    public function getUpcomingEntries(ContentContainerActiveRecord $contentContainer = null, $daysInFuture = 7, $limit = 5)
    {
        $start = new DateTime('now', CalendarUtils::getUserTimeZone());
        $end =  (new DateTime('now', CalendarUtils::getUserTimeZone()))
            ->add(new DateInterval('P'.$daysInFuture.'D'));

        $filters = [];

        if (!$contentContainer) {
            $filters[] = AbstractCalendarQuery::FILTER_DASHBOARD;
        }

        return $this->getCalendarItems($start, $end, $filters, $contentContainer, $limit);
    }

    /**
     * @param string $key item key
     * @return CalendarItemType|null
     */
    public function getItemType($key, ContentContainerActiveRecord $contentContainer = null)
    {
        $itemTypes = $this->getCalendarItemTypes($contentContainer);
        foreach ($itemTypes as $itemType) {
            if($itemType->key === $key) {
                return $itemType;
            }
        }

        return null;
    }
}