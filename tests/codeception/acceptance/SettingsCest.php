<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */


use calendar\AcceptanceTester;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 25.07.2017
 * Time: 23:49
 */
class SettingsCest
{
    public function testInstallAndCreatEntry(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->amOnRoute(['/calendar/config']);
        $I->waitForText('Calendar module configuration');
        $I->selectOption('#participationsettings-participation_mode', 0);
        $I->click('Save');

        $I->seeSuccess();

        $I->see('Reset', '.panel-body');

        $I->enableModule(1, 'calendar');

        $I->amOnSpace1('/calendar/view');
        $I->createEventToday('Setting Event','Setting Description',null,null,false);
        $I->click('.tab-participation');

        $I->seeInField('#calendarentry-participation_mode', 0);
        $I->dontSeeElement('.participationOnly');

        $I->amOnSpace1('/calendar/container-config');
        $I->selectOption('#participationsettings-participation_mode', 2);
        $I->click('[for="participationsettings-allow_decline"]');
        $I->click('[for="participationsettings-allow_maybe"]');

        $I->wait(2);
        $I->click('Save');
        $I->seeSuccess();
        $I->see('Reset', '.layout-content-container');

        $I->amOnSpace1('/calendar/view');
        $I->createEventToday('Setting Event','Setting Description',null,null,false);
        $I->click('.tab-participation');
        $I->seeInField('#calendarentry-participation_mode', 2);
        $I->seeElement('.participationOnly');
        $I->dontSeeCheckboxIsChecked('#calendarentry-allow_decline');
        $I->dontSeeCheckboxIsChecked('#calendarentry-allow_decline');

        $I->amOnSpace1('/calendar/container-config');
        $I->click('Reset', '.layout-content-container');

        $I->seeSuccess();

        $I->seeInField('#participationsettings-participation_mode', 0);

        $I->amOnRoute(['/calendar/config']);
        $I->click('Reset', '.panel-body');

        $I->seeSuccess();

        $I->seeInField('#participationsettings-participation_mode', 2);

        $I->seeCheckboxIsChecked('#participationsettings-allow_decline');
        $I->seeCheckboxIsChecked('#participationsettings-allow_decline');

        $I->amOnSpace1('/calendar/container-config');
        $I->seeCheckboxIsChecked('#participationsettings-allow_decline');
        $I->seeCheckboxIsChecked('#participationsettings-allow_decline');
    }

}