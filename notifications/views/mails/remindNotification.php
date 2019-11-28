<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\widgets\mails\MailButtonList;
use humhub\widgets\mails\MailButton;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $viewable humhub\modules\content\notifications\ContentCreated */
/* @var $url string */
/* @var $date string */
/* @var $isNew boolean */
/* @var $isNew boolean */
/* @var $originator \humhub\modules\user\models\User */
/* @var $source \humhub\modules\calendar\interfaces\CalendarEventReminderIF */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
/* @var $space humhub\modules\space\models\Space */
/* @var $record \humhub\modules\notification\models\Notification */
/* @var $html string */
/* @var $text string */

$formatter = new CalendarDateFormatter(['calendarItem' => $source])

?>
<?php $this->beginContent('@notification/views/layouts/mail.php', $_params_); ?>

    <table width="100%" style="table-layout:fixed;" border="0" cellspacing="0" cellpadding="0" align="left">
            <tr>
                <td colspan="2" style="word-wrap:break-word;padding-top:5px; padding-bottom:5px; font-size: 14px; line-height: 22px; font-family:Open Sans,Arial,Tahoma, Helvetica, sans-serif; color:<?= Yii::$app->view->theme->variable('text-color-main', '#777') ?>; font-weight:300; text-align:left; ?>;">

                    <?php if(!empty($source->getTitle()))  :?>
                        <h1><?= Html::encode($source->getTitle())?></h1>
                    <?php endif; ?>

                    <?php if(!empty($source->getStartDateTime())) : ?>
                       <?= Yii::t('CalendarModule.reminder', '<strong>Starting</strong> {date}', [
                            'date' => $formatter->getFormattedTime()
                        ]) ?>
                        <br>
                    <?php endif; ?>

                    <?php if(!empty($source->getLocation())) : ?>
                        <b><?= Yii::t('CalendarModule.reminder', 'Location:') ?> <?= Html::encode($source->getLocation())?></b>
                        <br>
                    <?php endif; ?>

                    <?php if(!empty($source->getDescription())) : ?>
                        <p><?= RichText::preview($source->getDescription()) ?></p>
                    <?php endif; ?>

                </td>
            </tr>
    </table>

    <?= MailButtonList::widget([
        'buttons' => [
            MailButton::widget(['url' => $url, 'text' => Yii::t('ContentModule.notifications_mails', 'View Online')])
        ]
    ]) ?>

<?php $this->endContent();
