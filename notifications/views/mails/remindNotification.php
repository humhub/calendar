<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/* @var $this yii\web\View */
/* @var $viewable humhub\modules\content\notifications\ContentCreated */
/* @var $url string */
/* @var $date string */
/* @var $isNew boolean */
/* @var $isNew boolean */
/* @var $originator \humhub\modules\user\models\User */
/* @var $source yii\db\ActiveRecord */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
/* @var $space humhub\modules\space\models\Space */
/* @var $record \humhub\modules\notification\models\Notification */
/* @var $html string */
/* @var $text string */

use humhub\widgets\mails\MailButtonList; ?>
<?php $this->beginContent('@notification/views/layouts/mail.php', $_params_); ?>

    asdffffffff

    <?= humhub\widgets\mails\MailContentEntry::widget([
            'originator' => $originator,
            'content' => $html,
            'date' => $date,
            'space' => $space
    ]) ?>

    <?= MailButtonList::widget([
        'buttons' => [
            humhub\widgets\mails\MailButton::widget(['url' => $url, 'text' => Yii::t('ContentModule.notifications_mails', 'View Online')])
        ]
    ]) ?>

<?php $this->endContent();
