<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\tests\codeception\unit\notifications;

use calendar\CalendarUnitTest;
use DateInterval;
use DateTime;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\notifications\CanceledEvent;
use humhub\modules\calendar\notifications\EventUpdated;
use humhub\modules\calendar\notifications\MarkAttend;
use humhub\modules\calendar\notifications\ParticipantAdded;
use humhub\modules\calendar\notifications\Remind;
use humhub\modules\calendar\notifications\ReopenedEvent;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\notification\components\NotificationBlock;
use humhub\modules\notification\components\NotificationContext;
use humhub\modules\notification\components\NotificationManager;
use humhub\modules\notification\components\NotificationPriority;
use humhub\modules\notification\models\Notification;
use humhub\modules\notification\targets\MailTarget;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;

/**
 * The sentences, mail subjects and mail blocks of the notifications about an event.
 */
class EventNotificationTest extends CalendarUnitTest
{
    private function createSpaceEntry(string $title = 'Team meeting'): CalendarEntry
    {
        $this->becomeUser('admin');

        return $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, $title, Space::findOne(['id' => 1]));
    }

    /**
     * A notification of the class about the entry, as the core builds it from its record.
     */
    private function build(string $class, CalendarEntry $entry, int $recipientId = 2, ?int $originatorId = 1): BaseNotification
    {
        $record = new Notification([
            'class' => $class,
            'user_id' => $recipientId,
            'originator_id' => $originatorId,
            'content_id' => $entry->content->id,
            'contentcontainer_id' => $entry->content->contentcontainer_id,
        ]);

        return NotificationManager::fromRecord($record);
    }

    public function testCategoryAndStandalone()
    {
        $this->assertEquals('calendar', CanceledEvent::category()->id);
        $this->assertEquals('calendar', Remind::category()->id);
        $this->assertTrue(EventUpdated::standalone());
        $this->assertEquals(NotificationPriority::High, Remind::priority());
    }

    public function testCanceledEventSentences()
    {
        $entry = $this->createSpaceEntry();
        $notification = $this->build(CanceledEvent::class, $entry);
        $originator = User::findOne(['id' => 1])->displayName;
        $space = Space::findOne(['id' => 1])->displayName;

        $this->assertStringContainsString('<strong>' . $originator . '</strong> canceled the event "Team meeting" in the space <strong>' . $space . '</strong>.', $notification->asWeb());
        $this->assertEquals($originator . ' canceled the event "Team meeting" in the space “' . $space . '”.', $notification->asPush());
        $this->assertEquals($originator . ' canceled the event "Team meeting".', $notification->asMailSubject());
        $this->assertEquals($originator . ' canceled an event.', $notification->asRedactedText());
    }

    public function testUpdatedAndReopenedSentences()
    {
        $entry = $this->createSpaceEntry();
        $originator = User::findOne(['id' => 1])->displayName;

        $updated = $this->build(EventUpdated::class, $entry);
        $this->assertStringContainsString('updated the event "Team meeting" in the space', $updated->asWeb());
        $this->assertEquals($originator . ' updated the event "Team meeting".', $updated->asMailSubject());
        $this->assertStringNotContainsString('Team meeting', $updated->asRedactedText());

        $reopened = $this->build(ReopenedEvent::class, $entry);
        $this->assertStringContainsString('reopened the event "Team meeting" in the space', $reopened->asWeb());
        $this->assertEquals($originator . ' reopened the event "Team meeting".', $reopened->asMailSubject());
    }

    public function testTitleIsEncodedInTheWebSentence()
    {
        $entry = $this->createSpaceEntry('Review <script>alert(1)</script>');
        $notification = $this->build(CanceledEvent::class, $entry);

        $this->assertStringNotContainsString('<script>', $notification->asWeb());
        $this->assertStringContainsString('Review', $notification->asWeb());
    }

    public function testMarkAttendSentences()
    {
        $entry = $this->createSpaceEntry();
        $notification = $this->build(MarkAttend::class, $entry, 1, 1);

        $this->assertStringContainsString('You have been registered for the event "Team meeting" in', $notification->asWeb());
        $this->assertStringContainsString('starting at ' . $entry->getFormattedTime(), $notification->asPush());
        $this->assertEquals('You have been registered for the event "Team meeting".', $notification->asMailSubject());
        $this->assertEquals('You have been registered for an event.', $notification->asRedactedText());
    }

    public function testParticipantAddedDependsOnTheParticipationStatus()
    {
        $entry = $this->createSpaceEntry();
        $user = User::findOne(['id' => 2]);
        $originator = User::findOne(['id' => 1])->displayName;

        $this->assertTrue($entry->participation->setParticipationStatus($user, CalendarEntryParticipant::PARTICIPATION_STATE_INVITED));
        $invited = $this->build(ParticipantAdded::class, $entry);
        $this->assertStringContainsString('invited you to the event "Team meeting" in the space', $invited->asWeb());
        $this->assertStringContainsString('starting at ' . $entry->getFormattedTime(), $invited->asPush());
        $this->assertEquals($originator . ' invited you to the event "Team meeting".', $invited->asMailSubject());
        $this->assertEquals($originator . ' invited you to an event.', $invited->asRedactedText());

        $this->assertTrue($entry->participation->setParticipationStatus($user, CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED));
        $added = $this->build(ParticipantAdded::class, $entry);
        $this->assertStringContainsString('added you to the event "Team meeting" in the space', $added->asWeb());
        $this->assertEquals($originator . ' added you to the event "Team meeting".', $added->asMailSubject());
    }

    public function testRemindSentences()
    {
        $entry = $this->createSpaceEntry();
        $notification = $this->build(Remind::class, $entry);

        $this->assertEquals('You have an <strong>Event</strong> coming up: Team meeting', $notification->asWeb());
        $this->assertEquals('You have an “Event” coming up: Team meeting', $notification->asPush());
        $this->assertEquals('Upcoming Event: Team meeting', $notification->asMailSubject());
        $this->assertEquals('You have an event coming up.', $notification->asRedactedText());
    }

    public function testMailShowsTheEventDetailsWithButtons()
    {
        $entry = $this->createSpaceEntry();
        $entry->location = 'Room 42';
        $entry->participant_info = 'Bring your laptop';
        $this->assertTrue($entry->save());

        $blocks = $this->build(EventUpdated::class, $entry)->getRenderedBlocks(new NotificationContext(MailTarget::ID));

        $this->assertCount(3, $blocks);
        $this->assertEquals(NotificationBlock::TYPE_HTML, $blocks[0]->getType());
        $this->assertStringContainsString('Team meeting', $blocks[0]->getHtml());
        $this->assertStringContainsString('Room 42', $blocks[0]->getHtml());
        $this->assertStringContainsString('Bring your laptop', $blocks[0]->getHtml());
        $this->assertStringContainsString('Room 42', $blocks[0]->getText());
        $this->assertEquals(NotificationBlock::TYPE_BUTTON, $blocks[1]->getType());
        $this->assertEquals('View online', $blocks[1]->getText());
        $this->assertEquals(NotificationBlock::TYPE_BUTTON, $blocks[2]->getType());
        $this->assertEquals('Download ICS', $blocks[2]->getText());
        $this->assertStringStartsWith('http', $blocks[2]->getUrl());
        $this->assertStringContainsString('calendar/export/event', urldecode($blocks[2]->getUrl()));

        // The participant information is only part of the mails about the participation
        $canceled = $this->build(CanceledEvent::class, $entry)->getRenderedBlocks(new NotificationContext(MailTarget::ID));
        $this->assertStringNotContainsString('Bring your laptop', $canceled[0]->getHtml());
    }

    public function testUpdateNotificationReachesTheParticipantsOnEveryUpdate()
    {
        $entry = $this->createSpaceEntry();
        $this->assertTrue($entry->participation->setParticipationStatus(User::findOne(['id' => 2]), CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED));
        $this->assertTrue($entry->participation->setParticipationStatus(User::findOne(['id' => 3]), CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED));

        $entry->participation->sendUpdateNotification();
        $this->assertHasNotification(EventUpdated::class, $entry, 1, 2);
        $this->assertHasNoNotification(EventUpdated::class, $entry, 1, 3);

        $entry->participation->sendUpdateNotification();
        $this->assertEqualsNotificationCount(2, EventUpdated::class, $entry, 1, 2);
    }
}
