<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\modules\user\models\User as User;
use humhub\modules\user\widgets\Image;
use humhub\widgets\AjaxLinkPager;
use humhub\widgets\Button;
use yii\data\Pagination;
use yii\helpers\Html;

/* @var CalendarEntry $entry */
/* @var User[] $users */
/* @var Pagination $pagination */
/* @var array $statuses */
/* @var array $options */
?>
<?= Html::beginTag('div', $options) ?>
<?php if (empty($users)): ?>
    <p><?= Yii::t('CalendarModule.views_entry_edit', 'No participants.'); ?></p>
<?php endif; ?>

<ul class="media-list">
    <?php foreach ($users as $user) : ?>
        <li data-user-id="<?= $user->id ?>">
            <div class="media">
                <a href="<?= $user->getUrl(); ?>" data-modal-close="1" class="media-body">
                    <?= Image::widget([
                        'user' => $user,
                        'link' => false,
                        'width' => 32,
                        'htmlOptions' => ['class' => 'media-object pull-left', 'style' => 'margin-right:5px'],
                    ]) ?>
                    <h4 class="media-heading"><?= Html::encode($user->displayName); ?></h4>
                    <h5><?= Html::encode($user->displayNameSub); ?></h5>
                </a>
                <div class="media-body" style="width:1%">
                    <?= Html::dropDownList('state', $entry->participation->getParticipationStatus($user), $statuses, [
                        'data-action-change' => 'update',
                    ]) ?>
                </div>
                <div class="media-body" style="width:1%;padding-left:5px">
                    <?= Button::danger(Icon::get('remove'))->xs()
                        ->confirm(null, Yii::t('CalendarModule.views_entry_edit', 'Are you sure want to remove the participant from the event?'))
                        ->action('remove') ?>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

<div class="pagination-container">
    <?= AjaxLinkPager::widget([
        'pagination' => $pagination,
        'jsBeforeSend' => 'function(){}',
        'jsSuccess' => 'function(html){ $("#globalModal .tab-pane.active").html(html); }',
    ]); ?>
</div>

<?= Html::endTag('div') ?>