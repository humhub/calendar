<?php

namespace humhub\modules\calendar\tests\codeception\unit;

use Yii;
use DateTime;
use DateInterval;
use tests\codeception\_support\HumHubDbTestCase;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ActiveQueryContent;

class CalendarEntryQueryTest extends HumHubDbTestCase
{

    /**
     * Test find dates by open range query.
     */
    public function testSimpleOpenRange()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        // Past (not included in range)
        $entry1 = $this->createEntry((new DateTime)->sub(new DateInterval('P5D')), 1, 'Past Entry', $s1);
        // From now till 5 days in future (included)
        $entry2 = $this->createEntry(null, 5, 'Entry 1', $s1);
        // From 3 days in future to 13 days in future (included)
        $entry3 = $this->createEntry((new DateTime)->add(new DateInterval('P3D')), 10, 'Entry 2', $s1);
        // Starts in 4 days (not included)
        $entry4 = $this->createEntry((new DateTime)->add(new DateInterval('P4D')), 6, 'Future Entry', $s1);


        $entries = CalendarEntryQuery::find()->days(3)->all();
        
        $this->assertEquals(2, count($entries));
        $this->assertEquals($entry2->title, $entries[0]->title);
        $this->assertEquals($entry3->title, $entries[1]->title);
    }

    /**
     * Test open range boundaries
     */
    public function testOpenRangeBoundary()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);
        
        // Whole day yesterday should be excluded
        $entry1 = $this->createEntry((new DateTime)->sub(new DateInterval('P1D'))->setTime(0,0,0), (new DateTime)->sub(new DateInterval('P1D'))->setTime(23,59,59), 'Yesterday', $s1);
        
        // Today
        $entry2 = $this->createEntry(null, (new DateTime)->setTime(23,59,59), 'Today', $s1);
        
        // Tomorrow
        $entry3 = $this->createEntry((new DateTime)->add(new DateInterval('P1D'))->setTime(0,0,0), (new DateTime)->add(new DateInterval('P1D'))->setTime(23,59,59), 'Tomorrow', $s1);
        
        // Get all entries from today by open range query
        $entries = CalendarEntryQuery::find()->days(0)->all();
        
        $this->assertEquals(1, count($entries));
        $this->assertEquals($entry2->title, $entries[0]->title);
        
        $entries = CalendarEntryQuery::find()->days(1)->all();
        $this->assertEquals(2, count($entries));
        $this->assertEquals($entry2->title, $entries[0]->title);
        $this->assertEquals($entry3->title, $entries[1]->title);
    }
    
    /**
     * Test finding entries for spaces content container
     */
    public function testSpaceContainerQuery()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);
        $s2 = Space::findOne(['id' => 2]);
        
        // Some entry way in the past (not included)
        $this->createEntry((new DateTime)->sub(new DateInterval('P30D')), 1, 'Past Entry', $s1);
        
        // Past but included for s1
        $entry1 = $this->createEntry((new DateTime)->sub(new DateInterval('P5D')), 1, 'Past Entry', $s1);
        
        // From now till 5 days in future (included for s1)
        $entry2 = $this->createEntry(null, 5, 'Entry 1', $s1);
        
        // From 3 days in future to 13 days in future (included for s2)
        $entry3 = $this->createEntry((new DateTime)->add(new DateInterval('P3D')), 10, 'Entry 1', $s2);
        
        // Starts in 4 days (not included for s2)
        $entry4 = $this->createEntry((new DateTime)->add(new DateInterval('P4D')), 6, 'Future Entry', $s2);
        
        // Entry way in the future (not included)
        $this->createEntry((new DateTime)->add(new DateInterval('P20D')), 6, 'Future Entry', $s2);
        
        // Find all within -5 till 13 day range
        $entries = CalendarEntryQuery::find()->from(-5)->to(13)->all();
        
        $this->assertEquals(4, count($entries));
        $this->assertEquals($entry1->title, $entries[0]->title);
        $this->assertEquals($entry2->title, $entries[1]->title);
        $this->assertEquals($entry3->title, $entries[2]->title);
        $this->assertEquals($entry4->title, $entries[3]->title);
        
        // Find all s1 entries within -5 till 13 day range
        $entries = CalendarEntryQuery::find()->container($s1)->from(-5)->to(13)->all();
        
        $this->assertEquals(2, count($entries));
        $this->assertEquals($entry1->title, $entries[0]->title);
        $this->assertEquals($entry2->title, $entries[1]->title);
        
        // Find all s1 entries within -5 till 13 day range
        $entries = CalendarEntryQuery::find()->container($s2)->from(-5)->to(13)->all();
        
        $this->assertEquals(2, count($entries));
        $this->assertEquals($entry3->title, $entries[0]->title);
        $this->assertEquals($entry4->title, $entries[1]->title);
    }
    
    /**
     * Test finding entries for user content container
     */
    public function testUserContainerQuery()
    {
        $this->becomeUser('Admin');
        $u1 = User::findOne(['id' => 1]);
        $u2 = User::findOne(['id' => 2]);
        
        // Some entry way in the past (not included)
        $this->createEntry((new DateTime)->sub(new DateInterval('P30D')), 1, 'Past Entry', $u1);
        
        // Past but included for s1
        $entry1 = $this->createEntry((new DateTime)->sub(new DateInterval('P5D')), 1, 'Past Entry', $u1);
        
        // From now till 5 days in future (included for s1)
        $entry2 = $this->createEntry(null, 5, 'Entry 1', $u1);
        
        // From 3 days in future to 13 days in future (included for s2)
        $entry3 = $this->createEntry((new DateTime)->add(new DateInterval('P3D')), 10, 'Entry 1', $u2);
        
        // Starts in 4 days (not included for s2)
        $entry4 = $this->createEntry((new DateTime)->add(new DateInterval('P4D')), 6, 'Future Entry', $u2);
        
        // Entry way in the future (not included)
        $this->createEntry((new DateTime)->add(new DateInterval('P20D')), 6, 'Future Entry', $u2);
        
        // Find all within -5 till 13 day range
        $entries = CalendarEntryQuery::find()->from(-5)->to(13)->all();
        
        $this->assertEquals(4, count($entries));
        $this->assertEquals($entry1->title, $entries[0]->title);
        $this->assertEquals($entry2->title, $entries[1]->title);
        $this->assertEquals($entry3->title, $entries[2]->title);
        $this->assertEquals($entry4->title, $entries[3]->title);
        
        // Find all s1 entries within -5 till 13 day range
        $entries = CalendarEntryQuery::find()->container($u1)->from(-5)->to(13)->all();
        
        $this->assertEquals(2, count($entries));
        $this->assertEquals($entry1->title, $entries[0]->title);
        $this->assertEquals($entry2->title, $entries[1]->title);
        
        // Find all s1 entries within -5 till 13 day range
        $entries = CalendarEntryQuery::find()->container($u2)->from(-5)->to(13)->all();
        
        $this->assertEquals(2, count($entries));
        $this->assertEquals($entry3->title, $entries[0]->title);
        $this->assertEquals($entry4->title, $entries[1]->title);
    }
    
    public function testUserRelated()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);
        $s2 = Space::findOne(['id' => 2]);
        $u1 = User::findOne(['id' => 1]);
        $u2 = User::findOne(['id' => 2]);
        
        // User related (include)
        $entry0 = $this->createEntry((new DateTime)->sub(new DateInterval('P30D')), 1, 'Past Entry', $u1);
        
        // User related (include)
        $entry1 = $this->createEntry((new DateTime)->sub(new DateInterval('P5D')), 1, 'Past Entry', $u1);
        
        // Other user (do not include)
        $entry2 = $this->createEntry(null, 5, 'Entry 1', $u2);
        
        // Member Space related (include)
        $entry3 = $this->createEntry((new DateTime)->add(new DateInterval('P3D')), 10, 'Entry 1', $s1);
        
        // Non member space (do not include)
        $entry4 = $this->createEntry((new DateTime)->add(new DateInterval('P4D')), 6, 'Future Entry', $s2);
        
        // By default select all user space and user profile related
        $entries = CalendarEntryQuery::find()->userRelated()->limit(20)->all();
        $this->assertEquals(3, count($entries));
        $this->assertEquals($entry0->title, $entries[0]->title);
        $this->assertEquals($entry1->title, $entries[1]->title);
        $this->assertEquals($entry3->title, $entries[2]->title);
        
        // All user space entries
        $entries = CalendarEntryQuery::find()->userRelated(ActiveQueryContent::USER_RELATED_SCOPE_SPACES)->limit(20)->all();
        $this->assertEquals(1, count($entries));
        $this->assertEquals($entry3->title, $entries[0]->title);
        
        // Add container user1 filter
        $entries = CalendarEntryQuery::find()->container($u1)->userRelated()->limit(20)->all();
        $this->assertEquals(2, count($entries));
        $this->assertEquals($entry0->title, $entries[0]->title);
        $this->assertEquals($entry1->title, $entries[1]->title);
        
        $s2->follow(null, false);
        
        $entries = CalendarEntryQuery::find()->userRelated([ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_SPACES])->limit(20)->all();
        $this->assertEquals(1, count($entries));
        $this->assertEquals($entry4->title, $entries[0]->title);
        
        $entry1->setParticipant($u1, CalendarEntryParticipant::PARTICIPATION_STATE_INVITED);
        
        $entries = CalendarEntryQuery::find()->userRelated()->invited()->limit(20)->all();
        $this->assertEquals(1, count($entries));
        $this->assertEquals($entry1->title, $entries[0]->title);
    }

    /*public function testFilterInvited()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);
        $s3 = Space::findOne(['id' => 3]);
        
        // User2 is member of both spaces
        $u2 = User::findOne(['id' => 3]);
        
        $entry0 = $this->createEntry((new DateTime)->sub(new DateInterval('P30D')), 1, 'Past Entry', $s1);
        
        $entry1 = $this->createEntry((new DateTime)->sub(new DateInterval('P5D')), 1, 'Past Entry', $s1);
        
        $entry2 = $this->createEntry(null, 5, 'Entry 1', $s1);
        
        $entry3 = $this->createEntry((new DateTime)->add(new DateInterval('P3D')), 10, 'Entry 1', $s3);
        
        $entry4 = $this->createEntry((new DateTime)->add(new DateInterval('P4D')), 6, 'Future Entry', $s1);
        
        $entry5 = $this->createEntry((new DateTime)->add(new DateInterval('P20D')), 6, 'Future Entry', $s3);
        
        $entry1->setParticipant($u2, CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED);
        $entry3->inviteParticipant($u2);
        $entry5->inviteParticipant($u2);
        
        $entries = CalendarEntryQuery::find($u2)->invited()->limit(20)->all();
        $this->assertEquals(2, count($entries));
        $this->assertEquals($entry3->title, $entries[0]->title);
        $this->assertEquals($entry5->title, $entries[1]->title);
    }*/
    
    public function testFilterParticipate()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);
        $s3 = Space::findOne(['id' => 3]);
        
        // User2 is member of both spaces
        $u2 = User::findOne(['id' => 3]);
        
        $entry0 = $this->createEntry((new DateTime)->sub(new DateInterval('P30D')), 1, 'Past Entry', $s1);
        
        $entry1 = $this->createEntry((new DateTime)->sub(new DateInterval('P5D')), 1, 'Past Entry', $s1);
        
        $entry2 = $this->createEntry(null, 5, 'Entry 1', $s1);
        
        $entry3 = $this->createEntry((new DateTime)->add(new DateInterval('P3D')), 10, 'Entry 1', $s3);
        
        $entry4 = $this->createEntry((new DateTime)->add(new DateInterval('P4D')), 6, 'Future Entry', $s1);
        
        $entry5 = $this->createEntry((new DateTime)->add(new DateInterval('P20D')), 6, 'Future Entry', $s3);
        
        // Create own date (automaticaly participant)
        $this->becomeUser('User2');
        $entry6 = $this->createEntry((new DateTime)->add(new DateInterval('P20D')), 6, 'Future Entry2', $s3);
        
        $entry1->setParticipant($u2, CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED);
        
        $entries = CalendarEntryQuery::find($u2)->participate()->limit(20)->all();
        $this->assertEquals(1, count($entries));
        $this->assertEquals($entry1->title, $entries[0]->title);
    }
    
    /**
     * Test find dates within a 1 day range (not open range).
     * 
     * E1: [Today - 5D] -> [Today - 4D]
     * E2: [Today]      -> [Today + 1D]
     * E3: [Today + 1D] -> [Today - 7D]
     * 
     * T: [[Today] -> [Today + 1D]] --> Start and end date within interval
     */
    public function testSimpleRange()
    {
        $this->becomeUser('Admin');
        $s1 = Space::findOne(['id' => 1]);

        $entry1 = $this->createEntry((new DateTime)->sub(new DateInterval('P5D')), 1, 'Past Entry', $s1);
        $entry2 = $this->createEntry(null, 1, 'Entry 1', $s1);
        $entry3 = $this->createEntry((new DateTime)->add(new DateInterval('P1D')), 6, 'Future Entry', $s1);


        $entries = CalendarEntryQuery::find()->days(1)->openRange(false)->all();
        $this->assertEquals(1, count($entries));
        $this->assertEquals('Entry 1', $entries[0]->title);
    }

    private function createEntry($from, $days, $title, $container = null)
    {
        if (!$from) {
            $from = new DateTime();
        }

        if(is_int($days)) {
            $to = clone $from;
            $to->add(new DateInterval("P" . $days . "D"));
        } else {
            $to = $days;
        }
        
        $entry = new CalendarEntry();
        $entry->title = $title;
        $entry->start_datetime = Yii::$app->formatter->asDateTime($from, 'php:Y-m-d') . " 00:00:00";
        $entry->end_datetime = Yii::$app->formatter->asDateTime($to, 'php:Y-m-d') . " 23:59:59";
        $entry->content->visibility = \humhub\modules\content\models\Content::VISIBILITY_PUBLIC;

        if($container) {
            $entry->content->container = $container;
        }

        $entry->save();

        return $entry;
    }
}
