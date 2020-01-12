<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace calendar\functional;

use calendar\FunctionalTester;
use DateTime;
use humhub\modules\space\behaviors\SpaceModelModules;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use tests\codeception\_pages\DashboardPage;
use Yii;

class CalendarWidgetCest
{
    /**
     * @skip fails on travis
     * @param FunctionalTester $I
     * @throws \Exception
     */
    public function testTestTheFriendshipBirthdayOnDashboard(FunctionalTester $I)
    {
        // Activate friendship
        $I->wantTo('test if birthdays are only visible for friend users on the dashboard upcoming events widget');
        $I->enableFriendships();

        // Login as User1
        $I->amUser1();

        $I->amGoingTo('set the birthday of User1 to tomorrow');
        $tomorrow = new DateTime('tomorrow');
        $birthday = (new DateTime())->setDate(1987, (int)$tomorrow->format('m'), (int)$tomorrow->format('d'));
        $I->setProfileField('birthday', $birthday->format('Y-m-d'));

        $I->wantTo('make sure that a non friend user does not see User1\'s birthday in the upcoming events on the dashboard');
        $I->switchIdentity(User::findOne(['username' => 'Admin']));
        DashboardPage::openBy($I);
        $I->dontSeeElement('.calendar-upcoming-snippet');

        $I->enableModule(3, 'calendar');

        $I->amOnSpace3();
        $I->seeElement('.calendar-upcoming-snippet');
        $I->see('Peter Tester Birthday', '.calendar-upcoming-snippet');

        $I->wantTo('make sure that I see the birthday of my friend User1 in the upcoming events widget');
        $I->amGoingTo('become friend with User1');
        $I->amFriendWith('User1');

        DashboardPage::openBy($I);
        $I->seeElement('.calendar-upcoming-snippet');
        $I->see('Peter Tester Birthday', '.calendar-upcoming-snippet');
    }
}
