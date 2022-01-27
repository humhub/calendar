<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\user\widgets\UserPickerField;
use humhub\widgets\Button;
use yii\helpers\Html;

/* @var array $statuses */
?>
<?= Html::beginTag('li', ['id' => 'calendar-entry-add-participants-form']) ?>
    <div class="media">
        <div class="media-body">
            <?= UserPickerField::widget([
                'name' => 'newParticipants',
                'placeholder' => Yii::t('AdminModule.user', 'Add new participants...'),
                'options' => ['label' => false],
            ]) ?>
        </div>
        <div class="media-body">
            <?= Html::dropDownList('status', '', $statuses) ?>
        </div>
        <div class="media-body">
            <?= Button::success()->xs()
                ->icon('add')
                ->action('add') ?>
        </div>
    </div>
<?= Html::endTag('li') ?>