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

$formatter = new CalendarDateFormatter(['calendarItem' => $event]);

?>

<div style="overflow:hidden">
    <table width="100%" style="table-layout:fixed;" border="0" cellspacing="0" cellpadding="0" align="left">
        <tr>
            <td colspan="2" style="word-wrap:break-word;padding-top:5px; padding-bottom:5px; font-size: 14px; line-height: 22px; font-family:Open Sans,Arial,Tahoma, Helvetica, sans-serif; color:<?= Yii::$app->view->theme->variable('text-color-main', '#777') ?>; font-weight:300; text-align:left;">

                <?php if(!empty($event->getTitle()))  :?>
                    <h1><?= Html::encode($event->getTitle())?></h1>
                <?php endif; ?>

                <?php if(!empty($event->getStartDateTime())) : ?>
                    <?= Yii::t('CalendarModule.mail', '<strong>Starting</strong> {date}', [
                        'date' => $formatter->getFormattedTime()
                    ]) ?>
                    <br>
                <?php endif; ?>

                <?php if($event instanceof CalendarEventParticipationIF) : ?>
                    <?php if($event->getOrganizer()) : ?>
                        <b><?= Yii::t('CalendarModule.mail', 'Organized by {userName}', ['userName' => Html::encode($event->getOrganizer()->displayName)]) ?></b>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if(!empty($event->getLocation())) : ?>
                    <b><?= Yii::t('CalendarModule.mail', 'Location:') ?> <?= Html::encode($event->getLocation())?></b>
                    <br>
                <?php endif; ?>

                <?php if(!empty($event->getDescription())) : ?>
                    <p><?= nl2br(RichText::preview($event->getDescription())) ?></p>
                <?php endif; ?>

                <?php if(isset($extraInfo)) : ?>
                    <h2><?= Yii::t('CalendarModule.mail', 'Additional information:'); ?></h2>
                    <p><?=  nl2br(RichText::preview($extraInfo)) ?></p>
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
