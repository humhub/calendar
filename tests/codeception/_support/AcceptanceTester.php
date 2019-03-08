<?php
namespace calendar;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \AcceptanceTester
{
    use _generated\AcceptanceTesterActions;

   /**
    * Define custom actions here
    */
   public function createEventToday($title = 'My Test Entry', $description = 'My Test Entry Description', $startTime = null, $endTime = null, $save = true)
   {
       $this->waitForElementVisible('.fc-today');
       $this->click('.fc-today');
       $this->waitForText('Create event');

       $this->fillField('CalendarEntry[title]', $title);
       $this->fillField('#calendarentry-description .humhub-ui-richtext'  , $description);

       if($startTime) {
           $this->click('[for="calendarentry-all_day"]');
           $this->wait(1);
           $this->fillField('#calendarentryform-start_time', $startTime);
           $this->fillField('#calendarentryform-start_time', $endTime);
       }

       if($save) {
           $this->click('Save', '#globalModal');
           $this->wait(1);
       }
   }
}
