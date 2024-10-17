<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\libs\Html;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\interfaces\participation\CalendarEventParticipationIF;
use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\modules\content\widgets\richtext\RichText;

/* @var $event CalendarEventIF */
/* @var $url string */
/* @var $extraInfo string */

if (!isset($url)) {
    $url = $event->getUrl();
}

$formatter = new CalendarDateFormatter(['calendarItem' => $event])
?>
<?= $event->getTitle() ?>

<?php if (!empty($event->getStartDateTime())): ?>
    <?= Yii::t('CalendarModule.notification', 'Starting') ?>: <?= $formatter->getFormattedTime() ?>
<?php endif; ?>

<?php if ($event instanceof CalendarEventParticipationIF && $event->getOrganizer()): ?>
    <?= Yii::t('CalendarModule.notification', 'Organizer') ?>: <?= Html::encode($event->getOrganizer()->displayName) ?>
<?php endif; ?>

<?php if (!empty($event->getLocation())): ?>
    <?= Yii::t('CalendarModule.notification', 'Location') ?>: <?= Html::encode($event->getLocation()) ?>
<?php endif; ?>

<?php if (!empty($event->getDescription())): ?>
    <?= Yii::t('CalendarModule.notification', 'Description') ?>:
    <?= RichText::preview($event->getDescription()) ?>
<?php endif; ?>

<?php if (isset($extraInfo) && !empty($extraInfo)): ?>
    <?= Yii::t('CalendarModule.notification', 'Participants info') ?>:
    <?= RichText::preview($extraInfo) ?>
<?php endif; ?>

<?= Yii::t('CalendarModule.notification', 'View Online: {url}', ['url' => $url]); ?>
