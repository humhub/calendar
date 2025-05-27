<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav;

use DateTime;
use humhub\modules\calendar\helpers\dav\enum\EventProperty;
use humhub\modules\calendar\helpers\dav\enum\EventVirtualProperty;
use humhub\modules\calendar\helpers\dav\enum\EventVisibilityValue;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use humhub\modules\user\models\User;

class EventProperties extends BaseObject
{
    /**
     * @var Property[]|null
     */
    private ?array $properties;

    public function from(string $calendar): self
    {
        $properties = Reader::read($calendar)->select('VEVENT')[0]->children();

        $this->properties = ArrayHelper::index(
            array_filter($properties, fn($property) => $property instanceof Property),
            function(Property $property) {
                return $property->name;
            }
        );

        return $this;
    }

    public function get(EventProperty|EventVirtualProperty $propertyKey, $default = null): mixed
    {
        if ($propertyKey == EventVirtualProperty::ALL_DAY) {
            $startDateTime = ArrayHelper::getValue($this->properties, EventProperty::START_DATE->value);
            $endDateTime = ArrayHelper::getValue($this->properties, EventProperty::END_DATE->value);

            return +(
                !empty($startDateTime['VALUE']) && $startDateTime['VALUE']->getValue() === 'DATE' &&
                !empty($endDateTime['VALUE']) && $endDateTime['VALUE']->getValue() === 'DATE'
            );
        }

        if ($propertyKey == EventVirtualProperty::DESCRIPTION_NORMALIZED) {
            $description = $this->get(EventProperty::DESCRIPTION);
            $description = preg_replace('/\n{2,}/', '<MULTI_N>', $description);
            $description = str_replace("\n", '', $description);
            $description = str_replace('<MULTI_N>', "\n", $description);
            return trim($description);
        }

        /** @var Property $property */
        $property = ArrayHelper::getValue($this->properties, $propertyKey->value, $default);

        if ($property !== $default) {
            if (in_array($propertyKey, [EventProperty::START_DATE, EventProperty::END_DATE])) {
                return (new DateTime($property->getValue()));
            }

            if ($propertyKey == EventProperty::VISIBILITY) {
                return EventVisibilityValue::from($property->getValue());
            }

        }

        return $property->getValue();
    }
}
