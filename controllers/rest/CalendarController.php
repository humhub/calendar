<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers\rest;

use humhub\modules\calendar\helpers\RestDefinitions;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\permissions\CreateEntry;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseContentController;
use Yii;

class CalendarController extends BaseContentController
{
    public static $moduleId = 'Calendar';

    /**
     * {@inheritdoc}
     */
    public function getContentActiveRecordClass()
    {
        return CalendarEntry::class;
    }

    /**
     * {@inheritdoc}
     */
    public function returnContentDefinition(ContentActiveRecord $contentRecord)
    {
        /** @var CalendarEntry $contentRecord */
        return RestDefinitions::getCalendarEntry($contentRecord);
    }

    private function saveCalendarEntry(CalendarEntryForm $calendarEntryForm): bool
    {
        $data = Yii::$app->request->bodyParams;

        return $calendarEntryForm->load($data) &&
            $calendarEntryForm->save() &&
            (!method_exists($this, 'updateContent') || $this->updateContent($calendarEntryForm->entry, $data));
    }

    public function actionCreate($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }
        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        if (!(new CalendarEntry($container))->content->canEdit()) {
            return $this->returnError(403, 'You are not allowed to create calendar entry!');
        }

        $calendarEntryForm = CalendarEntryForm::createEntry($container);

        if ($this->saveCalendarEntry($calendarEntryForm)) {
            return $this->returnContentDefinition($calendarEntryForm->entry);
        }

        if ($calendarEntryForm->hasErrors() || $calendarEntryForm->entry->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'entryForm' => $calendarEntryForm->getErrors(),
                'calendarEntry' => $calendarEntryForm->entry->getErrors(),
            ]);
        } else {
            Yii::error('Could not create validated calendar entry.', 'api');
            return $this->returnError(500, 'Internal error while save calendar entry!');
        }
    }

    public function actionUpdate($id)
    {
        $calendarEntry = CalendarEntry::findOne(['id' => $id]);
        if (! $calendarEntry) {
            return $this->returnError(404, 'Calendar entry not found!');
        }

        $calendarEntryForm = new CalendarEntryForm(['entry' => $calendarEntry]);
        if (! $calendarEntryForm->entry->content->canEdit()) {
            return $this->returnError(403, 'You are not allowed to update this calendar entry!');
        }

        if ($this->saveCalendarEntry($calendarEntryForm)) {
            return $this->returnContentDefinition($calendarEntryForm->entry);
        }

        if ($calendarEntryForm->hasErrors() || $calendarEntryForm->entry->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'entryForm' => $calendarEntryForm->getErrors(),
                'calendarEntry' => $calendarEntryForm->entry->getErrors(),
            ]);
        } else {
            Yii::error('Could not create validated calendar entry.', 'api');
            return $this->returnError(500, 'Internal error while update calendar entry!');
        }
    }

    public function actionRespond($id)
    {
        $calendarEntry = CalendarEntry::findOne(['id' => $id]);
        if (! $calendarEntry) {
            return $this->returnError(404, 'Calendar entry not found!');
        }
        if (!$calendarEntry->content->canView()) {
            return $this->returnError(403, 'You cannot view this content!');
        }

        $respondType = Yii::$app->request->post('type', null);

        if (is_null($respondType)) {
            return $this->returnError(400, 'Type field cannot be blank');
        }

        if (! in_array((int)$respondType, [
            CalendarEntryParticipant::PARTICIPATION_STATE_NONE,
            CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED,
            CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE,
            CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED,
        ], true)) {
            return $this->returnError(400, 'Invalid respond type');
        }

        if (!$calendarEntry->setParticipationStatus(Yii::$app->user->getIdentity(), (int)$respondType)) {
            return $this->returnError(500, 'Participation cannot be changed.');
        }

        return $this->returnSuccess('Participation successfully changed.');
    }
}
