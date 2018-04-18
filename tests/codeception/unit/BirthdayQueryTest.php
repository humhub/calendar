<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\tests\codeception\unit;

use DateInterval;
use DateTime;
use humhub\modules\calendar\integration\BirthdayCalendarQuery;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\space\models\Space;
use tests\codeception\_support\HumHubDbTestCase;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 16.07.2017
 * Time: 20:52
 */
class BirthdayQueryTest extends HumHubDbTestCase
{
    public function testFollowQuery()
    {
        $this->enableFriendships();
        $tomorrow = new DateTime('tomorrow');
        $birthday = (new DateTime())->setDate(1987, (int)$tomorrow->format('m'), (int)$tomorrow->format('d'));
        $this->setProfileField('birthday', $birthday->format('Y-m-d'), 'Admin');
        $this->setProfileField('birthday', $birthday->format('Y-m-d'), 'User2');

        $this->becomeUser('User1');

        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')), null, [BirthdayCalendarQuery::FILTER_USERRELATED => [ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS]]);
        $this->assertEquals(0, count($result));

        $this->follow('Admin');
        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')), null, [BirthdayCalendarQuery::FILTER_USERRELATED => [ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS]]);

        $this->assertEquals(1, count($result));

        $this->becomeFriendWith('User2');
        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')), null, [BirthdayCalendarQuery::FILTER_USERRELATED => [ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS]]);
        $this->assertEquals(2, count($result));
    }

    public function testFriendQuery()
    {
        $tomorrow = new DateTime('tomorrow');
        $birthday = (new DateTime())->setDate(1987, (int)$tomorrow->format('m'), (int)$tomorrow->format('d'));
        $this->setProfileField('birthday', $birthday->format('Y-m-d'), 'Admin');
        $this->setProfileField('birthday', $birthday->format('Y-m-d'), 'User2');
        $this->setProfileField('birthday', $birthday->format('Y-m-d'), 'User1');

        $this->becomeUser('Admin');
        $this->becomeFriendWith('User2');
        $this->enableFriendships(false);

        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')), null, [BirthdayCalendarQuery::FILTER_DASHBOARD]);
        $this->assertEquals(0, count($result));

        $this->enableFriendships();
        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')), null, [BirthdayCalendarQuery::FILTER_DASHBOARD]);
        $this->assertEquals(1, count($result));

        $this->becomeUser('User2');
        $this->becomeFriendWith('User1');
        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')), null, [BirthdayCalendarQuery::FILTER_DASHBOARD]);
        $this->assertEquals(2, count($result));
    }


    public function testBirthdayEdges()
    {

        $this->becomeUser('Admin');

        $testSpace = Space::findOne(['id' => 3]);

        // Test Year Change Range:  1.11.2010 - 1.2.2011
        $this->setProfileField('birthday', '1999-10-31', 'Admin');
        $this->setProfileField('birthday', '1945-11-05', 'User2');
        $this->setProfileField('birthday', '1956-01-03', 'User1');

        $result = BirthdayCalendarQuery::findForFilter(new DateTime("2010-11-01"), new DateTime("2011-02-01"), $testSpace);
        $this->assertEquals(2, count($result));

        $this->assertEquals('User2', $result[0]->username);
        $this->assertEquals('User1', $result[1]->username);

        // Test Range:  1.2.2010 - 1.4.2010
        $this->setProfileField('birthday', '1960-04-02', 'Admin');
        $this->setProfileField('birthday', '1905-02-01', 'User2');
        $this->setProfileField('birthday', '1900-01-30', 'User1');

        // Test Scope
        $result = BirthdayCalendarQuery::findForFilter(new DateTime("2010-02-01"), new DateTime("2010-03-01"), $testSpace);
        $this->assertEquals(1, count($result));
        $this->assertEquals($result[0]->username, 'User2');

        // Test Order
        $result = BirthdayCalendarQuery::findForFilter(new DateTime("2010-01-01"), new DateTime("2010-04-04"), $testSpace);
        $this->assertEquals(3, count($result));
        $this->assertEquals($result[1]->username, 'User2');

    }


    public function testDisabledUsers()
    {
        $this->becomeUser('Admin');
        $testSpace = Space::findOne(['id' => 3]);

        $testSpace->addMember(5);
        $testSpace->addMember(6);

        $this->setProfileField('birthday', '1910-10-31', 'Admin');
        $this->setProfileField('birthday', '1911-11-01', 'User2');
        $this->setProfileField('birthday', '1912-12-01', 'User1');
        $this->setProfileField('birthday', '1912-11-15', 'DisabledUser');
        $this->setProfileField('birthday', '1912-11-16', 'UnapprovedUser');

        $result = BirthdayCalendarQuery::findForFilter(new DateTime("2020-10-01"), new DateTime("2020-12-01"), $testSpace);
        $this->assertEquals(3, count($result));

    }

}