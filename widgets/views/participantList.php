<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\widgets\ParticipantAddForm;
use humhub\modules\calendar\widgets\ParticipantFilter;
use humhub\modules\calendar\widgets\ParticipantInviteForm;
use humhub\modules\calendar\widgets\ParticipantItem;
use humhub\modules\user\models\User as User;
use humhub\widgets\AjaxLinkPager;
use humhub\widgets\Button;
use yii\data\Pagination;
use yii\helpers\Html;

/* @var CalendarEntry $entry */
/* @var User[] $users */
/* @var Pagination $pagination */
/* @var array $options */
/* @var bool $initAddForm */
/* @var bool $initInviteForm */
?>
<?= ParticipantFilter::widget() ?>

<p style="padding:0 12px<?php if (!empty($users)) : ?>;display:none<?php endif; ?>"><?= Yii::t('CalendarModule.views_entry_edit', 'No participants.'); ?></p>

<?= Html::beginTag('ul', ['class' => 'media-list']) ?>
    <?php foreach ($users as $user) : ?>
        <?= ParticipantItem::widget([
            'entry' => $entry,
            'user' => $user,
        ])?>
    <?php endforeach; ?>
    <?php if ($initAddForm) : ?>
        <?= ParticipantAddForm::widget(['entry' => $entry]) ?>
    <?php endif; ?>
    <?php if ($initInviteForm) : ?>
        <?= ParticipantInviteForm::widget(['entry' => $entry]) ?>
    <?php endif; ?>
<?= Html::endTag('ul') ?>

<?php if (!$initAddForm && $entry->content->canEdit()) : ?>
    <?= Button::success(Yii::t('CalendarModule.views_entry_edit', 'Add participants'))
        ->cssClass('btn-participants-action')->sm()
        ->icon('add')
        ->action('displayForm', $entry->content->container->createUrl('/calendar/entry/add-participants-form', ['id' => $entry->id])) ?>
<?php endif; ?>
<?php if (!$initInviteForm && $entry->canInvite()) : ?>
    <?= Button::info(Yii::t('CalendarModule.views_entry_edit', 'Invite participants'))
        ->cssClass('btn-participants-action btn-participants-action-invite')->sm()
        ->icon('send')
        ->action('displayForm', $entry->content->container->createUrl('/calendar/entry/invite-participants-form', ['id' => $entry->id])) ?>
<?php endif; ?>

<div class="pagination-container">
    <?= AjaxLinkPager::widget([
        'pagination' => $pagination,
    ]); ?>
</div>