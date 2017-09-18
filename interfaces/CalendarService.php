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
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\base\Component;

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
        static $result = null;

        $containerKey = ($contentContainer) ? $contentContainer->contentcontainer_id : 'global';

        if($result === null) {
            $result = [];
            $result[$containerKey] = [];

            $event = new CalendarItemTypesEvent(['contentContainer' => $contentContainer]);
            $this->trigger(self::EVENT_GET_ITEM_TYPES, $event);

            foreach ($event->getTypes() as $key => $options) {
                $result[$containerKey][] = new CalendarItemType(['key' => $key, 'options' => $options, 'contentContainer' => $contentContainer]);
            }
        }

        return (isset($result[$containerKey])) ? $result[$containerKey] : [];
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
     * @return CalendarItem[]
     */
    public function getCalendarItems(DateTime $start, DateTime $end, $filters = [], ContentContainerActiveRecord $contentContainer = null, $limit = null)
    {
        $result = [];

        $event = new CalendarItemsEvent(['contentContainer' => $contentContainer, 'start' => $start, 'end' => $end, 'filters' => $filters, 'limit' => $limit]);
        $this->trigger(static::EVENT_FIND_ITEMS, $event);
        foreach($event->getItems() as $itemTypeKey => $items) {
            $itemType = $this->getItemType($itemTypeKey, $contentContainer);
            if($itemType && $itemType->isEnabled()) {
                foreach ($items as $item) {
                    $result[] = new CalendarItemWrapper(['itemType' => $itemType, 'options' => $item]);
                }
            }

        }

        $calendarEntries = CalendarEntryQuery::findForFilter($start, $end, $contentContainer, $filters, $limit);

        $result = array_merge($calendarEntries, $result);

        return (count($result) > $limit) ? array_slice($result, 0, $limit) : $result;
    }

    public function getUpcomingEntries(ContentContainerActiveRecord $contentContainer = null, $daysInFuture = 7, $limit = 5)
    {
        $start = new DateTime();

        $end = clone $start;
        $end->add(new DateInterval('P'.$daysInFuture.'D'));

        $filters = [];

        if ($contentContainer) {
            $filters['userRelated'] = [ActiveQueryContent::USER_RELATED_SCOPE_OWN_PROFILE, ActiveQueryContent::USER_RELATED_SCOPE_SPACES];
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