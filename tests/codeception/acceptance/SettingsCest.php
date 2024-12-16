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
        $I->jsClick('.tab-participation');
        $I->waitForText('Default participation settings');
        $I->selectOption('#participationsettings-participation_mode', '0');
        $I->click('Save');

        $I->seeSuccess();

        $I->jsClick('.tab-participation');
        $I->waitForText('Default participation settings');
        $I->see('Reset', '.tab-pane.active');

        $I->enableModule(1, 'calendar');

        $I->amOnSpace1('/calendar/view');
        $I->createEventToday('Setting Event', 'Setting Description', null, null, false);
        $I->click('Next', '#globalModal');

        $I->waitForText('Participants', null, '#globalModal');
        $I->seeInField('#calendarentry-participation_mode', '0');
        $I->dontSeeElement('.participationOnly');

        $I->amOnSpace1('/calendar/container-config');
        $I->jsClick('.tab-participation');
        $I->waitForText('Default participation settings');
        $I->selectOption('#participationsettings-participation_mode', '2');
        $I->click('[for="participationsettings-allow_decline"]');
        $I->click('[for="participationsettings-allow_maybe"]');

        $I->wait(2);
        $I->click('Save');
        $I->seeSuccess();
        $I->jsClick('.tab-participation');
        $I->waitForText('Default participation settings');
        $I->see('Reset', '.tab-pane.active');

        $I->amOnSpace1('/calendar/view');
        $I->createEventToday('Setting Event', 'Setting Description', null, null, false);
        $I->click('Next', '#globalModal');
        $I->waitForText('Participants', null, '#globalModal h4');
        $I->seeInField('#calendarentry-participation_mode', '2');
        $I->seeElement('.participationOnly');
        $I->dontSeeCheckboxIsChecked('#calendarentry-allow_decline');
        $I->dontSeeCheckboxIsChecked('#calendarentry-allow_decline');

        $I->amOnSpace1('/calendar/container-config');
        $I->jsClick('.tab-participation');
        $I->waitForText('Default participation settings');
        $I->click('Reset', '[data-ui-widget="calendar.participation.Form"]');

        $I->seeSuccess();

        $I->jsClick('.tab-participation');
        $I->waitForText('Default participation settings');
        $I->seeInField('#participationsettings-participation_mode', '0');

        $I->amOnRoute(['/calendar/config']);
        $I->jsClick('.tab-participation');
        $I->waitForText('Default participation settings');
        $I->click('Reset', '[data-ui-widget="calendar.participation.Form"]');

        $I->seeSuccess();

        $I->jsClick('.tab-participation');
        $I->waitForText('Default participation settings');
        $I->seeInField('#participationsettings-participation_mode', '2');

        $I->seeCheckboxIsChecked('#participationsettings-allow_decline');
        $I->seeCheckboxIsChecked('#participationsettings-allow_decline');

        $I->amOnSpace1('/calendar/container-config');
        $I->seeCheckboxIsChecked('#participationsettings-allow_decline');
        $I->seeCheckboxIsChecked('#participationsettings-allow_decline');
    }

}
