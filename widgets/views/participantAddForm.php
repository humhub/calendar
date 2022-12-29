<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\user\widgets\UserPickerField;
use humhub\widgets\Button;
use yii\helpers\Html;

/* @var ActiveForm $form */
/* @var CalendarEntryParticipationForm $model */
/* @var string $searchUsersUrl */
/* @var string $addParticipantsUrl */
/* @var array $statuses */
?>
<?= Html::beginTag('div', ['class' => 'calendar-entry-new-participants-form']) ?>
    <div class="media">
        <div class="media-body">
            <?= UserPickerField::widget([
                'model' => $model,
                'attribute' => 'newParticipants',
                'placeholder' => Yii::t('CalendarModule.base', 'Add participants...'),
                'options' => ['label' => false],
                'url' => $searchUsersUrl,
            ]) ?>
        </div>
        <div class="media-body">
            <?= $form->field($model, 'newParticipantStatus')->dropDownList($statuses)->label(false) ?>
        </div>
        <div class="media-body">
            <?= Button::info()->sm()
                ->icon('send')
                ->action('add', $addParticipantsUrl) ?>
        </div>
    </div>
    <?php if ($model->entry->participation->canAddAll()) : ?>
        <?= $form->field($model, 'forceJoin')->checkbox()
            ->label(Yii::t('CalendarModule.base', 'Add all Space members with status {status}', [
                'status' => $form->field($model, 'newForceStatus')->dropDownList($statuses)->label(false)
            ])) ?>
    <?php endif; ?>
<?= Html::endTag('div') ?>

<?= Html::tag('hr', '', ['style' => 'margin:10px 18px']) ?>