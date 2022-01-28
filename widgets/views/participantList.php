<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\widgets\ParticipantFilter;
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
?>
<?= Html::beginTag('div', $options) ?>
<?= ParticipantFilter::widget() ?>

<p style="padding:0 12px<?php if (!empty($users)) : ?>;display:none<?php endif; ?>"><?= Yii::t('CalendarModule.views_entry_edit', 'No participants.'); ?></p>

<?= Html::beginTag('ul', ['class' => 'media-list']) ?>
    <?php foreach ($users as $user) : ?>
        <?= ParticipantItem::widget([
            'entry' => $entry,
            'user' => $user,
        ])?>
    <?php endforeach; ?>
<?= Html::endTag('ul') ?>

<?php if ($entry->content->canEdit()) : ?>
    <?= Button::success(Yii::t('CalendarModule.views_entry_edit', 'Add participants'))
        ->sm()
        ->icon('add')
        ->action('displayAddForm', $entry->content->container->createUrl('/calendar/entry/add-participants-form')) ?>
<?php endif; ?>

<div class="pagination-container">
    <?= AjaxLinkPager::widget([
        'pagination' => $pagination,
    ]); ?>
</div>

<?= Html::endTag('div') ?>