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
 * Class to manage content records of the elements with Spaces list
 *
 * Dynamic attributes:
 * @property array $topic
 * @property int $limit
 */
class CalendarsElement extends BaseRecordsElement
{
    public const RECORD_CLASS = CalendarEntry::class;
    public string $subFormView = '@calendar/extensions/custom_pages/elements/views/calendars';

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Yii::t('CalendarModule.base', 'Calendars');
    }

    /**
     * @inheritdoc
     */
    protected function getDynamicAttributes(): array
    {
        return array_merge(parent::getDynamicAttributes(), [
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
            'topic' => Yii::t('CalendarModule.base', 'Topics'),
            'limit' => Yii::t('CalendarModule.base', 'Limit'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getTypes(): array
    {
        return array_merge(parent::getTypes(), [
            'topic' => Yii::t('CalendarModule.base', 'Calendars with specific topics'),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function getQuery(): ActiveQuery
    {
        $query = CalendarEntry::find()->readable();

        return match ($this->type) {
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

    protected function filterTopic(ActiveQuery $query): ActiveQuery
    {
        return $query->leftJoin('content_tag_relation', 'content_tag_relation.content_id = content.id')
            ->andWhere(['content_tag_relation.tag_id' => $this->topic]);
    }
}
