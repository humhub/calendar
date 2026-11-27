<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\tests\codeception\unit\notifications;

use calendar\RecurrenceUnitTest;
use DateTime;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\content\models\Content;
use humhub\modules\content\notifications\ContentCreatedNotification;
use humhub\modules\notification\models\Notification;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\user\notifications\MentionedNotification;

/**
 * The rich text of an entry is processed while the entry is saved, so a follower who is mentioned in a new entry
 * gets the mention only, not the "created" notification on top, and a generated recurrence does not mention again.
 */
class EntryMentionTest extends RecurrenceUnitTest
{
    private function mention(): string
    {
        return '[](mention:' . User::findOne(['id' => 1])->guid . ') please have a look';
    }

    private function createSpaceEntry(array $attributes): CalendarEntry
    {
        $start = (new DateTime())->modify('+1 day');
        $end = (clone $start)->modify('+1 hour');

        $entry = new CalendarEntry();
        $entry->title = 'Entry with a mention';
        $entry->all_day = 0;
        $entry->start_datetime = $start->format(CalendarUtils::DB_DATE_FORMAT);
        $entry->end_datetime = $end->format(CalendarUtils::DB_DATE_FORMAT);
        $entry->content->visibility = Content::VISIBILITY_PUBLIC;
        $entry->content->container = Space::findOne(['id' => 2]);
        $entry->setAttributes($attributes);

        $this->assertTrue($entry->save());

        return $entry;
    }

    private function countMentions(): int
    {
        return (int) Notification::find()
            ->where(['class' => MentionedNotification::class, 'user_id' => 1])
            ->count();
    }

    public function testMentionInTheDescriptionSendsTheMentionOnly()
    {
        $this->becomeUser('User2');

        // Admin follows Space 2 and is mentioned
        $entry = $this->createSpaceEntry(['description' => $this->mention()]);

        $this->assertEqualsNotificationCount(1, MentionedNotification::class, $entry, null, 1);
        $this->assertHasNoNotification(ContentCreatedNotification::class, $entry, null, 1);
    }

    public function testMentionInTheParticipationInfoSendsTheMentionOnly()
    {
        $this->becomeUser('User2');

        $entry = $this->createSpaceEntry(['participant_info' => $this->mention()]);

        $this->assertEqualsNotificationCount(1, MentionedNotification::class, $entry, null, 1);
        $this->assertHasNoNotification(ContentCreatedNotification::class, $entry, null, 1);
    }

    public function testFollowerWithoutMentionGetsTheCreatedNotification()
    {
        $this->becomeUser('User2');

        $entry = $this->createSpaceEntry(['description' => 'Nobody is mentioned']);

        $this->assertHasNoNotification(MentionedNotification::class, $entry, null, 1);
        $this->assertEqualsNotificationCount(1, ContentCreatedNotification::class, $entry, null, 1);
    }

    public function testGeneratedRecurrencesDoNotMentionAgain()
    {
        $this->becomeUser('User2');
        $space = Space::findOne(['id' => 2]);

        $this->rootEvent = $this->createEntry($this->getEntryDate(), 1, 'Recurrent Event', $space);
        $this->setDefaults($this->rootEvent, self::DEFAULT_RRULE);
        $this->rootEvent->description = $this->mention();
        $this->assertTrue($this->rootEvent->save());

        $this->assertSame(1, $this->countMentions());

        $recurrences = $this->expand(true);

        $this->assertNotEmpty($recurrences);
        $this->assertTrue(RecurrenceHelper::isRecurrentInstance($recurrences[0]));
        $this->assertSame(1, $this->countMentions());
    }

    public function testRecurrenceWithATextOfItsOwnIsProcessed()
    {
        $this->becomeUser('User2');
        $space = Space::findOne(['id' => 2]);

        $this->rootEvent = $this->createEntry($this->getEntryDate(), 1, 'Recurrent Event', $space);
        $this->setDefaults($this->rootEvent, self::DEFAULT_RRULE);
        $this->assertTrue($this->rootEvent->save());

        $recurrences = $this->expand(true);
        $this->assertSame(0, $this->countMentions());

        $recurrence = $recurrences[0];
        $recurrence->description = $this->mention();
        $this->assertTrue($recurrence->save());

        $this->assertSame(1, $this->countMentions());
    }
}
