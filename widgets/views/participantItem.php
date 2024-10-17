<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\widgets\ParticipantItem;
use humhub\modules\user\models\User as User;
use humhub\modules\user\widgets\Image;
use humhub\widgets\Button;
use yii\helpers\Html;

/* @var CalendarEntry $entry */
/* @var User $user */
/* @var array $statuses */
?>
<?= Html::beginTag('li', ['data-user-id' => $user->id]) ?>
    <div class="media">
        <a href="<?= $user->getUrl() ?>" data-modal-close="1" class="media-body">
            <?= Image::widget([
                'user' => $user,
                'link' => false,
                'width' => 32,
                'htmlOptions' => ['class' => 'media-object'],
            ]) ?>
            <h4 class="media-heading"><?= Html::encode($user->displayName) ?></h4>
            <h5><?= Html::encode($user->displayNameSub) ?></h5>
        </a>
        <div class="media-body">
            <?php if ($entry->content->canEdit()) : ?>
                <?= Html::dropDownList('status', $entry->participation->getParticipationStatus($user), $statuses, [
                    'data-action-change' => 'update',
                    'class' => 'form-control',
                ]) ?>
            <?php else : ?>
                <span class="label label-default"><?= ParticipantItem::getStatusTitle($entry->participation->getParticipationStatus($user)) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($entry->content->canEdit()) : ?>
            <div class="media-body">
                <?= Button::danger()->sm()
                    ->icon('remove')
                    ->confirm(null, Yii::t('CalendarModule.views', 'Are you sure want to remove the participant from the event?'))
                    ->action('remove') ?>
            </div>
        <?php endif; ?>
    </div>
<?= Html::endTag('li') ?>