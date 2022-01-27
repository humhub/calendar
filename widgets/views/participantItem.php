<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
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
            <?= Html::dropDownList('status', $entry->participation->getParticipationStatus($user), $statuses, [
                'data-action-change' => 'update',
            ]) ?>
        </div>
        <div class="media-body">
            <?= Button::danger()->xs()
                ->icon('remove')
                ->confirm(null, Yii::t('CalendarModule.views_entry_edit', 'Are you sure want to remove the participant from the event?'))
                ->action('remove') ?>
        </div>
    </div>
<?= Html::endTag('li') ?>