<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\user\widgets\UserPickerField;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;
use yii\helpers\Html;

/* @var ActiveForm $form */
/* @var CalendarEntryParticipationForm $model */
/* @var string $searchUsersUrl */
/* @var string $addParticipantsUrl */
/* @var array $statuses */
?>
<?= Html::beginTag('div', ['class' => 'calendar-entry-new-participants-form']) ?>
    <div class="d-flex flex-row">
        <div class="flex-fill pe-1">
            <?= UserPickerField::widget([
                'model' => $model,
                'attribute' => 'newParticipants',
                'placeholder' => Yii::t('CalendarModule.base', 'Add participants...'),
                'options' => ['label' => false],
                'url' => $searchUsersUrl,
            ]) ?>
        </div>
        <div class="pe-1">
            <?= $form->field($model, 'newParticipantStatus')->dropDownList($statuses)->label(false) ?>
        </div>
        <div>
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
