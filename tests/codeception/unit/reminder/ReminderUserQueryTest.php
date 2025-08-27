<?php

namespace humhub\modules\calendar\tests\codeception\unit\reminder;

use humhub\modules\calendar\models\participation\CalendarEntryParticipation;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use calendar\CalendarUnitTest;
use DateInterval;
use DateTime;

class ReminderUserQueryTest extends CalendarUnitTest
{
    /**
     * SPACE
     */

    public function testSpaceEntryWithoutParticipationRemindAllMembers()
    {
        $space = Space::findOne(['id' => 3]);
        $this->becomeUser('admin');

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, 'Test', $space);
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_NONE;
        $result = $entry->getReminderUserQuery()->all();
        static::assertCount(3, $result);
        static::assertParticipantInResult(User::findOne(['id' => 1]), $result);
        static::assertParticipantInResult(User::findOne(['id' => 2]), $result);
        static::assertParticipantInResult(User::findOne(['id' => 3]), $result);
    }

    public function testSpaceEntryWithParticipationRemindNoneIfNoParticipants()
    {
        $space = Space::findOne(['id' => 3]);
        $this->becomeUser('admin');

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, 'Test', $space);
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $result = $entry->getReminderUserQuery()->all();
        static::assertCount(0, $result);
    }

    public function testSpaceEntryWithParticipationRemindAcceptedParticipant()
    {
        $space = Space::findOne(['id' => 3]);
        $this->becomeUser('admin');

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, 'Test', $space);
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $entry->participation->setParticipationStatus(User::findOne(['id' => 2]), CalendarEntryParticipation::PARTICIPATION_STATUS_ACCEPTED);
        $result = $entry->getReminderUserQuery()->all();
        static::assertCount(1, $result);
        static::assertParticipantInResult(User::findOne(['id' => 2]), $result);
    }

    public function testSpaceEntryWithParticipationRemindMaybeParticipant()
    {
        $space = Space::findOne(['id' => 3]);
        $this->becomeUser('admin');

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, 'Test', $space);
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $entry->participation->setParticipationStatus(User::findOne(['id' => 2]), CalendarEntryParticipation::PARTICIPATION_STATUS_MAYBE);
        $result = $entry->getReminderUserQuery()->all();
        static::assertCount(1, $result);
        static::assertParticipantInResult(User::findOne(['id' => 2]), $result);
    }

    public function testSpaceEntryWithParticipationNotRemindDeclinedParticipant()
    {
        $space = Space::findOne(['id' => 3]);
        $this->becomeUser('admin');

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, 'Test', $space);
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $entry->participation->setParticipationStatus(User::findOne(['id' => 2]), CalendarEntryParticipation::PARTICIPATION_STATUS_DECLINED);
        $result = $entry->getReminderUserQuery()->all();
        static::assertCount(0, $result);
    }

    /**
     * User
     */

    public function testUserEntryWithoutParticipationRemindOnlyUser()
    {
        $user = User::findOne(['id' => 1]);
        $this->becomeUser('admin');

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, 'Test', $user);
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_NONE;
        $result = $entry->getReminderUserQuery()->all();
        static::assertCount(1, $result);
        static::assertParticipantInResult($user, $result);
    }


    public function testUserEntryWithParticipationRemindOnlyUserIfNoParticipants()
    {
        $user = User::findOne(['id' => 1]);
        $this->becomeUser('admin');

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, 'Test', $user);
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $result = $entry->getReminderUserQuery()->all();
        static::assertCount(1, $result);
        static::assertParticipantInResult($user, $result);
    }

    public function testUserEntryWithParticipationRemindUserAndAcceptedParticipant()
    {
        $user = User::findOne(['id' => 1]);
        $participant = User::findOne(['id' => 2]);
        $this->becomeUser('admin');

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, 'Test', $user);
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $entry->setParticipationStatus($participant, CalendarEntryParticipation::PARTICIPATION_STATUS_ACCEPTED);
        $result = $entry->getReminderUserQuery()->all();
        static::assertCount(2, $result);
        static::assertParticipantInResult($user, $result);
        static::assertParticipantInResult($participant, $result);
    }

    public function testUserEntryWithParticipationRemindUserAndMaybeParticipant()
    {
        $user = User::findOne(['id' => 1]);
        $participant = User::findOne(['id' => 2]);
        $this->becomeUser('admin');

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, 'Test', $user);
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $entry->setParticipationStatus($participant, CalendarEntryParticipation::PARTICIPATION_STATUS_MAYBE);
        $result = $entry->getReminderUserQuery()->all();
        static::assertCount(2, $result);
        static::assertParticipantInResult($user, $result);
        static::assertParticipantInResult($participant, $result);
    }

    public function testUserEntryWithParticipationRemindUserAndNotDeclinedUser()
    {
        $user = User::findOne(['id' => 1]);
        $participant = User::findOne(['id' => 2]);
        $this->becomeUser('admin');

        // Entry begins exactly in one hour
        $entry = $this->createEntry((new DateTime())->add(new DateInterval('PT1H')), null, 'Test', $user);
        $entry->participation_mode = CalendarEntryParticipation::PARTICIPATION_MODE_ALL;
        $entry->setParticipationStatus($participant, CalendarEntryParticipation::PARTICIPATION_STATUS_DECLINED);
        $result = $entry->getReminderUserQuery()->all();
        static::assertCount(1, $result);
        static::assertParticipantInResult($user, $result);
    }

    /**
     * @param $user User
     * @param $result User[]
     */
    private static function assertParticipantInResult(User $user, array $participants)
    {
        $found = false;
        foreach ($participants as $participant) {
            if ($user->is($participant)) {
                $found = true;
                break;
            }
        }

        static::assertTrue($found);
    }
}
