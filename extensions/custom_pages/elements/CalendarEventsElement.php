<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\extensions\custom_pages\elements;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\custom_pages\modules\template\elements\BaseContentRecordsElement;
use Yii;
use yii\db\ActiveQuery;

/**
 * Class to manage content records of the elements with Calendar events list
 */
class CalendarEventsElement extends BaseContentRecordsElement
{
    public const FILTER_PARTICIPANT = 'participant';
    public const RECORD_CLASS = CalendarEntry::class;
    public string $contentFormView = '@calendar/extensions/custom_pages/elements/views/calendars';

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
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getContentFilterOptions(): array
    {
        return array_merge([
            self::FILTER_PARTICIPANT => Yii::t('CalendarModule.views', 'I\'m attending'),
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

        return $query;
    }
}
