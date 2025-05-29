<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav;

use humhub\modules\calendar\helpers\dav\enum\EventProperty;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\topic\models\Topic;
use yii\base\BaseObject;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use humhub\modules\user\models\User;

class EventSync extends BaseObject
{
    private const PARTICIPATION_STATE_NONE = null;
    private const PARTICIPATION_STATE_ACCEPTED = 'ACCEPTED';
    private const PARTICIPATION_STATE_DECLINED = 'DECLINED';
    private const PARTICIPATION_STATE_MAYBE = 'TENTATIVE';
    private const PARTICIPATION_STATE_INVITED = 'NEEDS-ACTION';

    private const PARTICIPATION_STATE_MAP = [
        self::PARTICIPATION_STATE_NONE => CalendarEntryParticipant::PARTICIPATION_STATE_NONE,
        self::PARTICIPATION_STATE_ACCEPTED => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED,
        self::PARTICIPATION_STATE_DECLINED => CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED,
        self::PARTICIPATION_STATE_MAYBE => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE,
        self::PARTICIPATION_STATE_INVITED => CalendarEntryParticipant::PARTICIPATION_STATE_INVITED,
    ];

    private null|EventProperties $eventProperties;
    private null|CalendarEntry|ActiveRecord $event;

    public function from(EventProperties $eventProperties): self
    {
        $this->eventProperties = $eventProperties;

        return $this;
    }

    public function to(CalendarEntry $event)
    {
        $this->event = $event;
        $this->sync();
    }

    private function participants(): void
    {
        $attendeesRaw = $this->eventProperties->get(EventProperty::ATTENDEES, null, true);

        if (!empty($attendeesRaw) && is_iterable($attendeesRaw)) {
            $attendees = [];

            foreach ($attendeesRaw as $attendee) {
                $partStat = ArrayHelper::getValue($attendee, 'PARTSTAT')?->getValue() ?: null;
                $email = $attendee->getValue();

                if (strpos($email, 'mailto:') === 0) {
                    $email = substr($email, 7);
                }
                if ($email == $this->event->getOrganizer()->email) {
                    continue;
                }

                $user = User::findOne(['email' => $email]);
                if (!$user) {
                    continue;
                }

                $initialAttributes = [
                    'calendar_entry_id' => $this->event->id,
                    'user_id' => $user->id,
                ];
                $participant = CalendarEntryParticipant::findOne($initialAttributes);
                if (!$participant) {
                    $participant = CalendarEntryParticipant::findOne($initialAttributes) ?: new CalendarEntryParticipant($initialAttributes);
                    $participant->participation_state = ArrayHelper::getValue(self::PARTICIPATION_STATE_MAP, $partStat, CalendarEntryParticipant::PARTICIPATION_STATE_NONE);
                    $participant->save();
                }
                $attendees[] = $participant->id;

                $cleanUpCondition = [
                    'AND',
                    ['=', 'calendar_entry_id', $this->event->id],
                ];
                if (!empty($attendees)) {
                    $cleanUpCondition[] = ['NOT IN', 'id', $attendees];
                }
                CalendarEntryParticipant::deleteAll($cleanUpCondition);
            }
        }
    }

    private function recurrence(): void
    {
        $this->event->rrule = $this->eventProperties->get(EventProperty::RECURRENCE);
        $this->event->save();
    }

    private function categories()
    {
        $categories = $this->eventProperties->get(EventProperty::CATEGORIES, null, 0);

        $existingTopics = Topic::findByContainer($this->event->content->container, true)
            ->andWhere(['content_tag.name' => $categories])
            ->indexBy('content_tag.name')
            ->column();

        $categories = ArrayHelper::getColumn($categories, function($category) use ($existingTopics) {
            if ($existingTopic = ArrayHelper::getValue($existingTopics, $category)) {
                return $existingTopic;
            }

            return "_add:$category";
        });

        Topic::attach($this->event->content, $categories);
    }

    public function sync()
    {
        $this->participants();
        $this->recurrence();
        $this->categories();
    }
}
