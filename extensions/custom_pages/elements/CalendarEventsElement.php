<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\extensions\custom_pages\elements;

use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordsElement;
use Yii;
use yii\db\ActiveQuery;

/**
 * Class to manage content records of the elements with Calendar events list
 *
 * @property string $sortOrder
 * @property int $nextDays
 */
class CalendarEventsElement extends BaseContentRecordsElement
{
    public const FILTER_PARTICIPANT = 'participant';
    public const FILTER_PAST = 'past_events';
    public const SORT_DATE_OLD = 'date_old';
    public const SORT_DATE_NEW = 'date_new';
    public const RECORD_CLASS = CalendarEntry::class;
    public string $contentFormView = '@calendar/extensions/custom_pages/elements/views/calendars';

    /**
     * @inheritdoc
     */
    protected function getDynamicAttributes(): array
    {
        return array_merge(parent::getDynamicAttributes(), [
            'nextDays' => null,
            'sortOrder' => self::SORT_DATE_OLD,
        ]);
    }

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
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'static' => Yii::t('CalendarModule.base', 'Select calendars'),
            'sortOrder' => Yii::t('CalendarModule.base', 'Sorting'),
            'nextDays' => Yii::t('CalendarModule.base', 'Display events within the next X days'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'nextDays' => Yii::t('CalendarModule.base', 'Leave empty to list all events. Enter 0 to display only today\'s events.'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getContentFilterOptions(): array
    {
        return array_merge([
            self::FILTER_PAST => Yii::t('CalendarModule.base', 'Show past events'),
            self::FILTER_PARTICIPANT => Yii::t('CalendarModule.base', 'I\'m attending'),
        ], parent::getContentFilterOptions());
    }

    /**
     * @inheritdoc
     */
    protected function filterOptions(ActiveQueryContent $query): ActiveQuery
    {
        $query = parent::filterOptions($query);

        if (!Yii::$app->user->isGuest && $this->hasFilter(self::FILTER_PARTICIPANT)) {
            $query->leftJoin('calendar_entry_participant', 'calendar_entry.id = calendar_entry_participant.calendar_entry_id AND calendar_entry_participant.user_id = :userId', [':userId' => Yii::$app->user->id])
                ->andWhere(['calendar_entry_participant.participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED]);
        }

        if (!$this->hasFilter(self::FILTER_PAST)) {
            $now = new DateTime('now', CalendarUtils::getUserTimeZone());
            $query->andWhere(['>', 'calendar_entry.start_datetime', $now->format('Y-m-d H:i:s')]);
        }

        if ($this->nextDays !== null && $this->nextDays !== '') {
            $maxDate = new DateTime('+' . ($this->nextDays + 1) . ' days', CalendarUtils::getUserTimeZone());
            $query->andWhere(['<', 'calendar_entry.start_datetime', $maxDate->format('Y-m-d')]);
        }

        switch ($this->sortOrder) {
            case self::SORT_DATE_OLD:
                $query->orderBy(['calendar_entry.start_datetime' => SORT_ASC]);
                break;
            case self::SORT_DATE_NEW:
                $query->orderBy(['calendar_entry.start_datetime' => SORT_DESC]);
                break;
        }

        return $query;
    }
}
