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
<div class="media">
    <div class="media-body">
        <?= UserPickerField::widget([
            'name' => 'newParticipants',
            'placeholder' => Yii::t('AdminModule.user', 'Add new participants...'),
            'options' => ['label' => false],
        ]) ?>
    </div>
    <div class="media-body" style="width:1%">
        <?= Html::dropDownList('status', '', $statuses) ?>
    </div>
    <div class="media-body" style="width:1%;padding-left:5px">
        <?= Button::success()->xs()
            ->icon('add')
            ->action('add') ?>
    </div>
</div>