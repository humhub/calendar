<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\widgets\ParticipantAdd;
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
<?php if (empty($users)): ?>
    <p><?= Yii::t('CalendarModule.views_entry_edit', 'No participants.'); ?></p>
<?php endif; ?>

<ul class="media-list">
    <?php foreach ($users as $user) : ?>
        <?= ParticipantItem::widget([
            'entry' => $entry,
            'user' => $user,
        ])?>
    <?php endforeach; ?>
    <li id="calendar-entry-add-participants-form" style="display:none">
        <?= ParticipantAdd::widget() ?>
    </li>
</ul>

<?= Button::info(Yii::t('CalendarModule.views_entry_edit', 'Add participants'))
    ->sm()
    ->action('displayAddForm') ?>

<div class="pagination-container">
    <?= AjaxLinkPager::widget([
        'pagination' => $pagination,
        'jsBeforeSend' => 'function(){}',
        'jsSuccess' => 'function(html){ $("#globalModal .tab-pane.active").html(html); }',
    ]); ?>
</div>

<?= Html::endTag('div') ?>