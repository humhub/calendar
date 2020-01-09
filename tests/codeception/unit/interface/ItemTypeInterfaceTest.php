<?php


namespace humhub\modules\calendar\tests\codeception\unit;

use calendar\CalendarUnitTest;
use humhub\modules\calendar\interfaces\event\CalendarItemTypesEvent;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\modules\calendar\interfaces\event\CalendarTypeIF;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\calendar\tests\codeception\unit\models\OtherTestEventType;
use humhub\modules\calendar\tests\codeception\unit\models\TestEventType;
use humhub\modules\space\models\Space;

class ItemTypeInterfaceTest extends CalendarUnitTest
{
    public function testExportCalendarItemType()
    {
        $service = new CalendarService();
        $service->on(CalendarService::EVENT_GET_ITEM_TYPES, function(CalendarItemTypesEvent $event) {
            $event->addType(TestEventType::ITEM_TYPE, new TestEventType());
        });

        $this->assertContainsType($service->getCalendarItemTypes(), new TestEventType());
    }

    public function testExportMultipleCalendarItemType()
    {
        $service = new CalendarService();
        $service->on(CalendarService::EVENT_GET_ITEM_TYPES, function(CalendarItemTypesEvent $event) {
            $event->addType(TestEventType::ITEM_TYPE, new TestEventType());
            $event->addType(OtherTestEventType::ITEM_TYPE, new OtherTestEventType());
        });

        $types = $service->getCalendarItemTypes();

        $this->assertContainsType($types, new TestEventType());
        $this->assertContainsType($types, new OtherTestEventType());
    }

    public function testLegacyCalendarItemTypeInterface()
    {
        $service = new CalendarService();
        $service->on(CalendarService::EVENT_GET_ITEM_TYPES, function(CalendarItemTypesEvent $event) {
            $event->addType('arrayType', [
                'title' => 'Array Test Type',
                'description' => 'Array Test Type Description',
                'color' => '#111111',
                'icon' => 'fa-calendar-o'
            ]);
        });

        $types = $service->getCalendarItemTypes();

        $found = $this->assertContainsType($types, 'arrayType');
        $this->assertEquals('Array Test Type',$found->getTitle());
        $this->assertEquals('Array Test Type Description',$found->getDescription());
        $this->assertEquals('#111111',$found->getDefaultColor());
        $this->assertEquals('fa-calendar-o',$found->getIcon());
    }

    public function testAddItemToContainer()
    {
        $space1 = Space::findOne(['id' => 1]);
        $space2 = Space::findOne(['id' => 2]);

        $service = new CalendarService();
        $service->on(CalendarService::EVENT_GET_ITEM_TYPES, function(CalendarItemTypesEvent $event) {
            $container = $event->contentContainer;

            if(!$container || $container->id === 1) {
                $event->addType(TestEventType::ITEM_TYPE, new TestEventType());
            }

            if(!$container || $container->id === 2) {
                $event->addType(OtherTestEventType::ITEM_TYPE, new OtherTestEventType());
            }
        });

        // Test GLobal
        $this->assertContainsType($service->getCalendarItemTypes(), new TestEventType());
        $this->assertContainsType($service->getCalendarItemTypes(), new OtherTestEventType());

        $this->assertContainsType($service->getCalendarItemTypes($space1), new TestEventType());
        $this->assertNotContainsType($service->getCalendarItemTypes($space1), new OtherTestEventType());

        $this->assertNotContainsType($service->getCalendarItemTypes($space2), new TestEventType());
        $this->assertContainsType($service->getCalendarItemTypes($space2), new OtherTestEventType());
    }

    /**
     * @param $result CalendarEntryType[]
     * @param CalendarTypeIF|string $searchType
     * @return CalendarEntryType
     */
    private function assertContainsType($result, $searchType) {
        $searchType = $searchType instanceof CalendarTypeIF ? $searchType->getKey() : $searchType;
        $found = $this->findTypeInArray($result,$searchType);
        $this->assertNotNull($found, 'Could not find type: '.$searchType);
        return $found;
    }

    private function assertNotContainsType($result, $searchType) {
        $searchType = $searchType instanceof CalendarTypeIF ? $searchType->getKey() : $searchType;
        $found = $this->findTypeInArray($result,$searchType);
        $this->assertFalse($found, 'Should not find type: '.$searchType);
        return $found;
    }

    /**
     * @param $result CalendarEntryType[]
     * @param CalendarTypeIF|string $searchType
     * @return false|CalendarEntryType
     */
    private function findTypeInArray($result, $searchType) {
        $found = false;
        foreach ($result as $type) {
            if($type->getKey() === $searchType) {
                $found = $type;
            }
        }

        return $found;
    }
}