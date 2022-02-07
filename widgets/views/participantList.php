<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\calendar\widgets\ParticipantAddForm;
use humhub\modules\calendar\widgets\ParticipantFilter;
use humhub\modules\calendar\widgets\ParticipantItem;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\user\models\User as User;
use humhub\widgets\AjaxLinkPager;
use yii\data\Pagination;
use yii\helpers\Html;

/* @var ActiveForm $form */
/* @var CalendarEntryParticipationForm $model */
/* @var User[] $users */
/* @var Pagination $pagination */
/* @var array $options */
?>
<?= ParticipantFilter::widget() ?>

<p class="calendar-entry-participants-count"><?= $pagination->totalCount
    ? Yii::t('CalendarModule.views_entry_edit', '{count} participants', ['count' => $pagination->totalCount])
    : Yii::t('CalendarModule.views_entry_edit', 'No participants')
?></p>

<?= ParticipantAddForm::widget(['form' => $form, 'model' => $model]) ?>

<?= Html::beginTag('ul', ['class' => 'media-list']) ?>
    <?php foreach ($users as $user) : ?>
        <?= ParticipantItem::widget([
            'entry' => $model->entry,
            'user' => $user,
        ])?>
    <?php endforeach; ?>
<?= Html::endTag('ul') ?>

<div class="pagination-container">
    <?= AjaxLinkPager::widget([
        'pagination' => $pagination,
        'jsBeforeSend' => 'function(){}',
    ]); ?>
</div>