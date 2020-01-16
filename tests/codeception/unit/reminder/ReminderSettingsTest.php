<?php

namespace humhub\modules\calendar\tests\codeception\unit\reminder;

use calendar\CalendarUnitTest;
use DateInterval;
use DateTime;
use humhub\modules\calendar\models\reminder\forms\ReminderSettings;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

class ReminderSettingsTest extends CalendarUnitTest
{
    public function testDisableContainerReminder()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        $form = new ReminderSettings();

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
            ]
        ]));

        $this->assertTrue($form->save());

        $form = new ReminderSettings(['container' => $space]);
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => null]
            ]
        ]));
        $this->assertTrue($form->save());

        $form = new ReminderSettings(['container' => $space]);
        $this->assertCount(2, $form->reminders);
        $this->assertEquals(1, $form->reminders[0]->disabled);

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        // Load entry form
        $form = new ReminderSettings(['entry' => $entry]);
        $this->assertCount(2, $form->reminders);
        $this->assertEquals(1, $form->reminders[0]->disabled);

        // Load user entry form
        $form = new ReminderSettings(['entry' => $entry, 'user' => User::findOne(['id' => 1])]);
        $this->assertCount(2, $form->reminders);
        $this->assertEquals(1, $form->reminders[0]->disabled);
    }

    public function testDisableEntryReminder()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        $form = new ReminderSettings();

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
            ]
        ]));

        $this->assertTrue($form->save());

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        $form = new ReminderSettings(['entry' => $entry]);
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => null]
            ]
        ]));
        $this->assertTrue($form->save());

        // Reload entry form
        $form = new ReminderSettings(['entry' => $entry]);
        $this->assertCount(2, $form->reminders);
        $this->assertEquals(1, $form->reminders[0]->disabled);

        // Load user entry form
        $form = new ReminderSettings(['entry' => $entry, 'user' => User::findOne(['id' => 1])]);
        $this->assertCount(2, $form->reminders);
        $this->assertEquals(1, $form->reminders[0]->disabled);
    }

    public function testDisableUserEntryReminder()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        $form = new ReminderSettings();

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
            ]
        ]));

        $this->assertTrue($form->save());

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        $form = new ReminderSettings(['entry' => $entry, 'user' => User::findOne(['id' => 1])]);
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => null]
            ]
        ]));
        $this->assertTrue($form->save());

        // Load user entry form
        $form = new ReminderSettings(['entry' => $entry, 'user' => User::findOne(['id' => 1])]);
        $this->assertCount(2, $form->reminders);
        $this->assertEquals(1, $form->reminders[0]->disabled);
    }

    public function testSaveGlobal()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        $form = new ReminderSettings();

        // Only the initial reminder is present
        $this->assertCount(1, $form->reminders);
        $this->assertTrue($form->reminders[0]->isNewRecord);

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
            ]
        ]));

        $this->assertTrue($form->save());

        // Reload global form
        $form = new ReminderSettings();
        $this->assertCount(3, $form->reminders);

        // Load container form
        $form = new ReminderSettings(['container' => $space]);
        $this->assertCount(3, $form->reminders);

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        // Load entry form
        $form = new ReminderSettings(['entry' => $entry]);
        $this->assertCount(3, $form->reminders);

        // Load user entry form
        $form = new ReminderSettings(['entry' => $entry, 'user' => User::findOne(['id' => 1])]);
        $this->assertCount(3, $form->reminders);
    }

    public function testSaveContainer()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        $form = new ReminderSettings();

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
            ]
        ]));

        $this->assertTrue($form->save());


        // Load container form
        $form = new ReminderSettings(['container' => $space]);
        $this->assertCount(3, $form->reminders);

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 3],
            ]
        ]));

        $this->assertTrue($form->save());

        // Reload container settings
        $form = new ReminderSettings(['container' => $space]);
        $this->assertCount(4, $form->reminders);

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        // Load entry form
        $form = new ReminderSettings(['entry' => $entry]);
        $this->assertCount(4, $form->reminders);

        // Load user entry form
        $form = new ReminderSettings(['entry' => $entry, 'user' => User::findOne(['id' => 1])]);
        $this->assertCount(4, $form->reminders);
    }

    public function testSaveEntryLevel()
    {
        Yii::$app->getModule('calendar')->maxReminder = 100;

        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        $form = new ReminderSettings();

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
            ]
        ]));

        $this->assertTrue($form->save());


        // Load container form
        $form = new ReminderSettings(['container' => $space]);
        $this->assertCount(3, $form->reminders);

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 3],
            ]
        ]));

        $this->assertTrue($form->save());

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        // Load entry form
        $form = new ReminderSettings(['entry' => $entry]);

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 3],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 4],
            ]
        ]));

        $this->assertTrue($form->save());

        // Reload entry level settings
        $form = new ReminderSettings(['entry' => $entry]);
        $this->assertCount(5, $form->reminders);

        // Load user entry form
        $form = new ReminderSettings(['entry' => $entry, 'user' => User::findOne(['id' => 1])]);
        $this->assertCount(5, $form->reminders);
    }

    public function testSaveUserEntryLevel()
    {
        Yii::$app->getModule('calendar')->maxReminder = 100;

        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        $form = new ReminderSettings();

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
            ]
        ]));

        $this->assertTrue($form->save());


        // Load container form
        $form = new ReminderSettings(['container' => $space]);
        $this->assertCount(3, $form->reminders);

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 3],
            ]
        ]));

        $this->assertTrue($form->save());

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);


        // Load entry form
        $form = new ReminderSettings(['entry' => $entry]);

        // Save global
        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 3],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 4],
            ]
        ]));

        $this->assertTrue($form->save());

        // Load user entry form
        $form = new ReminderSettings(['entry' => $entry, 'user' => User::findOne(['id' => 1])]);

        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_CUSTOM
            ],
            'CalendarReminder' => [
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 3],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 4],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 5],
            ]
        ]));

        $this->assertTrue($form->save());

        // Reload user entry form
        $form = new ReminderSettings(['entry' => $entry, 'user' => User::findOne(['id' => 1])]);
        $this->assertCount(6, $form->reminders);
    }

    public function testFlagsGlobal()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);
        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 1, $space)->save();
        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 2, $space)->save();
        $form = new ReminderSettings();
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_NONE, $form->reminderType);
        $this->assertFalse($form->hasDefaults());
        $this->assertFalse($form->isDefaultsLoaded());
        $this->assertTrue($form->isGlobalSettings());
        $this->assertFalse($form->isEntryLevelSettings());
        $this->assertFalse($form->isUserLevelEntrySettings());
    }

    public function testFlagsContainerLevelNoDefaults()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 1, $space)->save();
        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 2, $space)->save();

        $form = new ReminderSettings(['container' => $space]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_CUSTOM, $form->reminderType);
        $this->assertFalse($form->hasDefaults());
        $this->assertFalse($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertTrue($form->isContainerLevelSettings());
        $this->assertFalse($form->isEntryLevelSettings());
        $this->assertFalse($form->isUserLevelEntrySettings());
    }

    public function testFlagsContainerLevelWithDefaults()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1)->save();
        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 2)->save();

        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 1, $space)->save();
        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 2, $space)->save();

        $form = new ReminderSettings(['container' => $space]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_CUSTOM, $form->reminderType);
        $this->assertTrue($form->hasDefaults());
        $this->assertFalse($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertTrue($form->isContainerLevelSettings());
        $this->assertFalse($form->isEntryLevelSettings());
        $this->assertFalse($form->isUserLevelEntrySettings());
    }

    public function testFlagsContainerLevelWithDefaultsLoaded()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1)->save();
        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 2)->save();

        $form = new ReminderSettings(['container' => $space]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_DEFAULT, $form->reminderType);
        $this->assertTrue($form->hasDefaults());
        $this->assertTrue($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertTrue($form->isContainerLevelSettings());
        $this->assertFalse($form->isEntryLevelSettings());
        $this->assertFalse($form->isUserLevelEntrySettings());
    }

    public function testFlagsEntryLevelWithoutDefaults()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry)->save();
        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 2, $entry)->save();

        $form = new ReminderSettings(['entry' => $entry]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_CUSTOM, $form->reminderType);
        $this->assertFalse($form->hasDefaults());
        $this->assertFalse($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertFalse($form->isContainerLevelSettings());
        $this->assertTrue($form->isEntryLevelSettings());
        $this->assertFalse($form->isUserLevelEntrySettings());
    }

    public function testFlagsEntryLevelWithGlobalDefaults()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1)->save();
        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 2)->save();

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry)->save();
        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 2, $entry)->save();

        $form = new ReminderSettings(['entry' => $entry]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_CUSTOM, $form->reminderType);
        $this->assertTrue($form->hasDefaults());
        $this->assertFalse($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertFalse($form->isContainerLevelSettings());
        $this->assertTrue($form->isEntryLevelSettings());
        $this->assertFalse($form->isUserLevelEntrySettings());
    }

    public function testFlagsEntryLevelWithContainerDefaults()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 1, $space)->save();
        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 2, $space)->save();

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry)->save();
        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 2, $entry)->save();

        $form = new ReminderSettings(['entry' => $entry]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_CUSTOM, $form->reminderType);
        $this->assertTrue($form->hasDefaults());
        $this->assertFalse($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertFalse($form->isContainerLevelSettings());
        $this->assertTrue($form->isEntryLevelSettings());
        $this->assertFalse($form->isUserLevelEntrySettings());
    }

    public function testFlagsEntryLevelWithContainerDefaultsLoaded()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 1, $space)->save();
        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 2, $space)->save();

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        $form = new ReminderSettings(['entry' => $entry]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_DEFAULT, $form->reminderType);
        $this->assertTrue($form->hasDefaults());
        $this->assertTrue($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertFalse($form->isContainerLevelSettings());
        $this->assertTrue($form->isEntryLevelSettings());
        $this->assertFalse($form->isUserLevelEntrySettings());
    }

    public function testFlagsEntryLevelWithGlobalDefaultsLoaded()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);


        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1)->save();
        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 2)->save();

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        $form = new ReminderSettings(['entry' => $entry]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_DEFAULT, $form->reminderType);
        $this->assertTrue($form->hasDefaults());
        $this->assertTrue($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertFalse($form->isContainerLevelSettings());
        $this->assertTrue($form->isEntryLevelSettings());
        $this->assertFalse($form->isUserLevelEntrySettings());
    }

    public function testFlagsUserEntryLevelWithoutDefaults()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);
        $user =  User::findOne(['id' => 1]);

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        $form = new ReminderSettings(['entry' => $entry, 'user' => $user]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_NONE, $form->reminderType);
        $this->assertFalse($form->hasDefaults());
        $this->assertFalse($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertFalse($form->isContainerLevelSettings());
        $this->assertTrue($form->isEntryLevelSettings());
        $this->assertTrue($form->isUserLevelEntrySettings());
    }

    public function testFlagsUserEntryLevelWithGlobalDefaults()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);
        $user =  User::findOne(['id' => 1]);

        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1)->save();
        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 2)->save();

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry, $user)->save();
        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 2, $entry, $user)->save();

        $form = new ReminderSettings(['entry' => $entry, 'user' => $user]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_CUSTOM, $form->reminderType);
        $this->assertTrue($form->hasDefaults());
        $this->assertFalse($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertFalse($form->isContainerLevelSettings());
        $this->assertTrue($form->isEntryLevelSettings());
        $this->assertTrue($form->isUserLevelEntrySettings());
    }

    public function testFlagsUserEntryLevelWithContainerDefaults()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);
        $user =  User::findOne(['id' => 1]);

        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 1, $space)->save();
        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 2, $space)->save();

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry, $user)->save();
        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 2, $entry, $user)->save();

        $form = new ReminderSettings(['entry' => $entry, 'user' => $user]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_CUSTOM, $form->reminderType);
        $this->assertTrue($form->hasDefaults());
        $this->assertFalse($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertFalse($form->isContainerLevelSettings());
        $this->assertTrue($form->isEntryLevelSettings());
        $this->assertTrue($form->isUserLevelEntrySettings());
    }

    public function testFlagsUserEntryLevelWithEntryDefaults()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);
        $user =  User::findOne(['id' => 1]);

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry)->save();
        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 2, $entry)->save();

        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry, $user)->save();
        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 2, $entry, $user)->save();

        $form = new ReminderSettings(['entry' => $entry, 'user' => $user]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_CUSTOM, $form->reminderType);
        $this->assertTrue($form->hasDefaults());
        $this->assertFalse($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertFalse($form->isContainerLevelSettings());
        $this->assertTrue($form->isEntryLevelSettings());
        $this->assertTrue($form->isUserLevelEntrySettings());
    }

    public function testFlagsUserEntryLevelWithEntryDefaultsLoaded()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);
        $user =  User::findOne(['id' => 1]);

        $entry = $this->createEntry((new DateTime)->add(new DateInterval('PT1H')), null, 'Test',  $space);

        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 1, $entry)->save();
        CalendarReminder::initEntryLevel(CalendarReminder::UNIT_HOUR, 2, $entry)->save();

        $form = new ReminderSettings(['entry' => $entry, 'user' => $user]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_DEFAULT, $form->reminderType);
        $this->assertTrue($form->hasDefaults());
        $this->assertTrue($form->isDefaultsLoaded());
        $this->assertFalse($form->isGlobalSettings());
        $this->assertFalse($form->isContainerLevelSettings());
        $this->assertTrue($form->isEntryLevelSettings());
        $this->assertTrue($form->isUserLevelEntrySettings());
    }

    public function testUseDefaultsContainer()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1)->save();
        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 2)->save();

        // Load user entry form
        $form = new ReminderSettings(['container' => $space]);

        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_DEFAULT
            ],
            'CalendarReminder' => [ // Will be irgnored
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 3],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 4],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 5],
            ]
        ]));

        $this->assertTrue($form->save());

        //Reload
        $form = new ReminderSettings(['container' => $space]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_DEFAULT, $form->reminderType);
        $this->assertTrue($form->isDefaultsLoaded());
        $this->assertCount(3, $form->reminders);
    }

    public function testUseDefaultsContainerWithReset()
    {
        $this->becomeUser('admin');
        $space = Space::findOne(['id' => 3]);

        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 1)->save();
        CalendarReminder::initGlobalDefault(CalendarReminder::UNIT_HOUR, 2)->save();

        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 1, $space)->save();
        CalendarReminder::initContainerDefault(CalendarReminder::UNIT_HOUR, 2, $space)->save();

        // Load user entry form
        $form = new ReminderSettings(['container' => $space]);

        $this->assertCount(3, $form->reminders);

        $this->assertTrue($form->load([
            'ReminderSettings' => [
                'reminderType' => ReminderSettings::REMINDER_TYPE_DEFAULT
            ],
            'CalendarReminder' => [  // Will be irgnored
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 1],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 2],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 3],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 4],
                ['unit' => CalendarReminder::UNIT_HOUR, 'value' => 5],
            ]
        ]));

        $this->assertTrue($form->save());

        //Reload
        $form = new ReminderSettings(['container' => $space]);
        $this->assertEquals(ReminderSettings::REMINDER_TYPE_DEFAULT, $form->reminderType);
        $this->assertTrue($form->isDefaultsLoaded());
        $this->assertCount(3, $form->reminders);
    }
}