<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\notifications\base;

use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\widgets\mails\CalendarEventMailInfo;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\notification\components\NotificationBlock;
use humhub\modules\notification\components\NotificationCategory;
use humhub\modules\notification\components\NotificationContext;
use Yii;

/**
 * Base of the notifications about a calendar event, which are sent about the content of the event.
 *
 * A mail shows the details of the event (time, organizer, location, description) below the
 * sentence, with a button to open the event and one to download it as an ICS file. The details
 * are the point of the mail, so every calendar notification goes out as a mail of its own.
 */
abstract class EventNotification extends BaseNotification
{
    /**
     * @var bool whether the mail also shows the participant information of the event
     */
    protected bool $withParticipantInfo = false;

    /**
     * @inheritdoc
     */
    public static function category(): NotificationCategory
    {
        return NotificationCategory::ofModule(static::class, 'ti-calendar-event');
    }

    /**
     * @inheritdoc
     */
    public static function standalone(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getBlocks(NotificationContext $context): array
    {
        $event = $this->getEvent();
        if ($event === null) {
            return parent::getBlocks($context);
        }

        $extraInfo = $this->withParticipantInfo && $event instanceof CalendarEntry ? $event->participant_info : null;

        $blocks = [
            NotificationBlock::html(
                CalendarEventMailInfo::html($event, $extraInfo, $this->recipient),
                CalendarEventMailInfo::text($event, $extraInfo),
            ),
        ];

        if ($this->getUrl() !== null) {
            $blocks[] = NotificationBlock::button(Yii::t('NotificationModule.base', 'View online'), $this->getEntryUrl());
        }

        if ($event instanceof ContentActiveRecord) {
            $blocks[] = NotificationBlock::button(
                Yii::t('CalendarModule.base', 'Download ICS'),
                Url::toEntryDownloadICS($event, null, true),
            );
        }

        return $blocks;
    }

    /**
     * The event the notification is about; `null` when its record is gone.
     */
    protected function getEvent(): ?CalendarEventIF
    {
        $record = $this->content?->getPolymorphicRelation();

        return $record instanceof CalendarEventIF ? $record : null;
    }

    /**
     * The event as an entry of this module; `null` for the event of another module.
     */
    protected function getEntry(): ?CalendarEntry
    {
        $event = $this->getEvent();

        return $event instanceof CalendarEntry ? $event : null;
    }

    /**
     * The formatted time of the event, the `time` parameter of a sentence.
     */
    protected function getFormattedTime(): string
    {
        $event = $this->getEvent();
        if ($event === null) {
            return '';
        }

        return (string)(new CalendarDateFormatter(['calendarItem' => $event]))->getFormattedTime();
    }
}
