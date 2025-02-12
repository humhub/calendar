<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\extensions\custom_pages\elements;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\custom_pages\modules\template\elements\BaseRecordsElement;
use Yii;
use yii\db\ActiveQuery;

/**
 * Class to manage content records of the elements with Calendar events list
 *
 * Dynamic attributes:
 * @property array $space
 * @property array $topic
 * @property int $limit
 */
class CalendarEventsElement extends BaseRecordsElement
{
    public const RECORD_CLASS = CalendarEntry::class;
    public string $subFormView = '@calendar/extensions/custom_pages/elements/views/calendars';

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Yii::t('CalendarModule.base', 'Calendar events');
    }

    /**
     * @inheritdoc
     */
    protected function getDynamicAttributes(): array
    {
        return array_merge(parent::getDynamicAttributes(), [
            'space' => null,
            'topic' => null,
            'limit' => null,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'static' => Yii::t('CalendarModule.base', 'Select calendars'),
            'space' => Yii::t('CalendarModule.base', 'Spaces'),
            'topic' => Yii::t('CalendarModule.base', 'Topics'),
            'limit' => Yii::t('CalendarModule.base', 'Limit'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'space' => Yii::t('CalendarModule.base', 'Leave empty to list calendar events from all spaces.'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getTypes(): array
    {
        return array_merge(parent::getTypes(), [
            'space' => Yii::t('CalendarModule.base', 'Calendars from specific spaces'),
            'topic' => Yii::t('CalendarModule.base', 'Calendars with specific topics'),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function isConfigured(): bool
    {
        return parent::isConfigured() || $this->type === 'space';
    }

    /**
     * @inheritdoc
     */
    protected function getQuery(): ActiveQuery
    {
        $query = CalendarEntry::find()->readable();

        return match ($this->type) {
            'space' => $this->filterSpace($query),
            'topic' => $this->filterTopic($query),
            default => $this->filterStatic($query),
        };
    }

    /**
     * @inheritdoc
     */
    protected function filterStatic(ActiveQuery $query): ActiveQuery
    {
        return $query->andWhere(['calendar_entry.id' => $this->static]);
    }

    protected function filterSpace(ActiveQuery $query): ActiveQuery
    {
        return empty($this->space)
            ? $query->andWhere(['IS NOT', 'space.id', null])
            : $query->andWhere(['space.guid' => $this->space]);
    }

    protected function filterTopic(ActiveQuery $query): ActiveQuery
    {
        return $query->leftJoin('content_tag_relation', 'content_tag_relation.content_id = content.id')
            ->andWhere(['content_tag_relation.tag_id' => $this->topic]);
    }

    /**
     * @inheritdoc
     */
    public function isCacheable(): bool
    {
        // Don't cache because the filter `CalendarEntry::find()->readable()` is used here
        return true;
    }
}
