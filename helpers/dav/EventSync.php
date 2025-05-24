<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use Sabre\VObject\Reader;
use Sabre\VObject\Component\VEvent;
use yii\base\BaseObject;
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

    private ?VEvent $vEvent;
    private ?CalendarEntry $event;

    public function from(string $calendar): self
    {
        $this->vEvent = Reader::read($calendar)->select('VEVENT')[0];

        return $this;
    }

    public function to(CalendarEntry $event): self
    {
        $this->event = $event;

        return $this;
    }

    private function participants(): void
    {
        if (!empty($this->vEvent->ATTENDEE) && is_iterable($this->vEvent->ATTENDEE)) {
            $attendees = [];

            foreach ($this->vEvent->ATTENDEE as $attendee) {
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
        if (!empty($this->vEvent->RRULE)) {
            $this->event->rrule = $this->vEvent->RRULE->getValue();
            $this->event->recurrence_id = null; // Assuming root event has null recurrence_id
        } else {
            $this->event->rrule = null;
        }
    }

    public function __destruct()
    {
        $this->participants();
        $this->recurrence();
    }
}
