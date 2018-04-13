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
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

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

        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')) , null, [BirthdayCalendarQuery::FILTER_USERRELATED => [ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS]]);
        $this->assertEquals(0, count($result));

        $this->follow('Admin');
        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')) , null, [BirthdayCalendarQuery::FILTER_USERRELATED => [ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS]]);

        $this->assertEquals(1, count($result));

        $this->becomeFriendWith('User2');
        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')) , null, [BirthdayCalendarQuery::FILTER_USERRELATED => [ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS]]);
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

        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')) , null, [BirthdayCalendarQuery::FILTER_DASHBOARD]);
        $this->assertEquals(0, count($result));

        $this->enableFriendships();
        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')) , null, [BirthdayCalendarQuery::FILTER_DASHBOARD]);
        $this->assertEquals(1, count($result));

        $this->becomeUser('User2');
        $this->becomeFriendWith('User1');
        $result = BirthdayCalendarQuery::findForFilter(new DateTime(), (new DateTime)->add(new DateInterval('P10D')) , null, [BirthdayCalendarQuery::FILTER_DASHBOARD]);
        $this->assertEquals(2, count($result));
    }
}