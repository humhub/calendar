<?php

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\helpers\Url;
use humhub\widgets\Button;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use Yii;

/**
 * Description of EntryParticipants
 *
 * @author luke
 */
class EntryParticipants extends Widget
{

    /**
     * @var CalendarEntry
     */
    public $calendarEntry;

    public function run()
    {
        if ($this->calendarEntry->closed) {
            return '';
        }

        $countAttending = $this->getParticipantStateCount(CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED, true);
        $countMaybe = $this->getParticipantStateCount(CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE, $this->calendarEntry->allow_maybe);
        $countDeclined = $this->getParticipantStateCount(CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED, $this->calendarEntry->allow_decline);
        $countInvited = $this->getParticipantStateCount(CalendarEntryParticipant::PARTICIPATION_STATE_INVITED, true);

        return $this->render('participants', [
            'calendarEntry' => $this->calendarEntry,
            'countAttending' => $countAttending,
            'countMaybe' => $countMaybe,
            'countDeclined' => $countDeclined,
            'countInvited' => $countInvited,
        ]);
    }

    private function getParticipantStateCount($state, $condition)
    {
        if(!$condition) {
            return null;
        }

        return  $this->calendarEntry->getParticipantCount($state);
    }

    public static function participateButton(CalendarEntry $calendarEntry, $state, $label)
    {
        if($state == CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE && !$calendarEntry->allow_maybe) {
            return null;
        }
        if($state == CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED && !$calendarEntry->allow_decline) {
            return null;
        }

        $participantSate = $calendarEntry->getParticipationStatus(Yii::$app->user->identity);
        return Button::info($label)->sm()
                     ->icon($participantSate === $state ? 'fa-check' : null)
                     ->action('calendar.respond', Url::toEntryRespond($calendarEntry, $state));
    }

    public static function inviteButton(CalendarEntry $calendarEntry): string
    {
        if ($calendarEntry->participation_mode !== CalendarEntry::PARTICIPATION_MODE_INVITE) {
            return '';
        }

        if (!$calendarEntry->isOwner()) {
            return '';
        }

        return Button::asLink(Yii::t('CalendarModule.views_entry_view', 'Invite participants'), Url::toInviteParticipants($calendarEntry))
            ->cssClass('btn btn-primary')->sm()
            ->icon('fa-users')
            ->options(['data-target' => '#globalModal']);
    }
}
