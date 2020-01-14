<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\libs\Html;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\participation\CalendarEventParticipationIF;use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\widgets\mails\MailButton;
use humhub\widgets\mails\MailButtonList;

/* @var $event CalendarEventIF */
/* @var $url string */
/* @var $extraInfo string*/

if(!isset($url)) {
    $url = $event->getUrl();
}

$formatter = new CalendarDateFormatter(['calendarItem' => $event])

?>
<?= $event->getTitle() ?>


<?php if(!empty($event->getStartDateTime())) : ?>
<?= strip_tags(Yii::t('CalendarModule.mail', '<strong>Starting</strong> {date}', [
    'date' => $formatter->getFormattedTime()
])) ?>
<?php endif; ?>
<?php if($event instanceof CalendarEventParticipationIF) : ?>
<?php if($event->getOrganizer()) : ?>


<?= Yii::t('CalendarModule.mail', 'Organized by {userName}', ['userName' => $event->getOrganizer()->displayName]) ?>

<?php endif; ?>
<?php endif; ?>
<?php if(!empty($event->getLocation())) : ?>

<?= Yii::t('CalendarModule.mail', 'Location:') ?> <?= $event->getLocation() ?>

<?php endif; ?>
<?php if(!empty($event->getDescription())) : ?>

<?= RichText::preview($event->getDescription()) ?>

<?php endif; ?>
<?php if(isset($extraInfo)) : ?>
<?= Yii::t('CalendarModule.mail', 'Additional information:'); ?>


<?= RichText::preview($extraInfo) ?>
<?php endif; ?>

<?= Yii::t('CalendarModule.mail', 'View Online: {url}', ['url' => $url]); ?>
