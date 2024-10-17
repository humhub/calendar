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
use humhub\modules\content\widgets\richtext\converter\RichTextToEmailHtmlConverter;
use humhub\modules\ui\mail\DefaultMailStyle;
use humhub\modules\ui\view\components\View;
use humhub\widgets\mails\MailButton;
use humhub\widgets\mails\MailButtonList;

/* @var $this View */
/* @var $event CalendarEventIF */
/* @var $url string */
/* @var $extraInfo string */

if (!isset($url)) {
    $url = $event->getUrl();
}

$formatter = new CalendarDateFormatter(['calendarItem' => $event]);
?>

<div style="overflow:hidden">
    <table width="100%" style="table-layout:fixed;" border="0" cellspacing="0" cellpadding="0" align="left">
        <tr>
            <td colspan="2"
                style="word-wrap:break-word;padding-top:5px; padding-bottom:5px; font-size: 14px; line-height: 22px; font-family:<?= $this->theme->variable('mail-font-family', DefaultMailStyle::DEFAULT_FONT_FAMILY) ?>; color:<?= $this->theme->variable('text-color-main', '#555') ?>; font-weight:300; text-align:left;">

                <?php if (!empty($event->getTitle())): ?>
                    <h1><?= Html::encode($event->getTitle()) ?></h1>
                <?php endif; ?>

                <?php if (!empty($event->getStartDateTime())): ?>
                    <strong><?= Yii::t('CalendarModule.notification', 'Starting') ?>:</strong>
                    <?= $formatter->getFormattedTime() ?><br><br>
                <?php endif; ?>

                <?php if ($event instanceof CalendarEventParticipationIF && $event->getOrganizer()): ?>
                    <strong><?= Yii::t('CalendarModule.notification', 'Organizer') ?>:</strong>
                    <?= Html::encode($event->getOrganizer()->displayName) ?><br><br>
                <?php endif; ?>

                <?php if (!empty($event->getLocation())): ?>
                    <strong><?= Yii::t('CalendarModule.notification', 'Location') ?>:</strong>
                    <?= Html::encode($event->getLocation()) ?><br><br>
                <?php endif; ?>

                <?php if (!empty($event->getDescription())): ?>
                    <strong><?= Yii::t('CalendarModule.notification', 'Description') ?>:</strong><br>
                    <?= RichTextToEmailHtmlConverter::process($event->getDescription()) ?><br>
                <?php endif; ?>

                <?php if (isset($extraInfo) && !empty($extraInfo)): ?>
                    <strong><?= Yii::t('CalendarModule.notification', 'Participants info') ?>:</strong><br>
                    <?= RichTextToEmailHtmlConverter::process($extraInfo) ?><br>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>
<?= MailButtonList::widget([
    'buttons' => [
        MailButton::widget(['url' => $url, 'text' => Yii::t('ContentModule.notifications_mails', 'View Online')])
    ]
]) ?>
