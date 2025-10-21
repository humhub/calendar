<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\widgets\ParticipantItem;
use humhub\modules\user\models\User;
use humhub\modules\user\widgets\Image;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;
use yii\helpers\Html;

/* @var CalendarEntry $entry */
/* @var User $user */
/* @var array $statuses */
?>
<?= Html::beginTag('div', ['data-user-id' => $user->id, 'class' => 'd-flex align-items-center']) ?>
    <a href="<?= $user->getUrl() ?>" data-modal-close="1" class="d-flex flex-grow-1">
        <?= Image::widget([
            'user' => $user,
            'link' => false,
            'width' => 32,
        ]) ?>
        <div class="ms-1">
            <h4 class="m-0"><?= Html::encode($user->displayName) ?></h4>
            <h5 class="mt-1 mb-0"><?= Html::encode($user->displayNameSub) ?></h5>
        </div>
    </a>
    <div class="ms-1">
        <?php if (!$entry->content->canEdit()) : ?>
            <?= Html::dropDownList('status', $entry->participation->getParticipationStatus($user), $statuses, [
                'data-action-change' => 'update',
                'class' => 'form-control',
            ]) ?>
        <?php else : ?>
            <?= Badge::light(ParticipantItem::getStatusTitle($entry->participation->getParticipationStatus($user))) ?>
        <?php endif; ?>
    </div>
    <?php if ($entry->content->canEdit()) : ?>
        <div class="ms-1">
            <?= Button::danger()->sm()
                ->icon('remove')
                ->confirm(null, Yii::t('CalendarModule.views', 'Are you sure want to remove the participant from the event?'))
                ->action('remove') ?>
        </div>
    <?php endif; ?>
<?= Html::endTag('div') ?>
