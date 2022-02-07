<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\calendar\widgets\ParticipantItem;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\user\widgets\UserPickerField;
use humhub\widgets\Button;
use yii\helpers\Html;

/* @var ActiveForm $form */
/* @var CalendarEntryParticipationForm $model */
?>
<?= Html::beginTag('div', ['class' => 'calendar-entry-new-participants-form']) ?>
    <div class="media">
        <div class="media-body">
            <?= UserPickerField::widget([
                'name' => 'newParticipants',
                'placeholder' => Yii::t('AdminModule.user', 'Add new participants...'),
                'options' => ['label' => false],
            ]) ?>
        </div>
        <div class="media-body">
            <?= Html::dropDownList('status', '', ParticipantItem::getStatuses($model->entry->canInvite()), ['class' => 'form-control']) ?>
        </div>
        <div class="media-body">
            <?= Button::info()->sm()
                ->icon('send')
                ->action('add', $model->entry->content->container->createUrl('/calendar/entry/add-participants')) ?>
        </div>
    </div>
    <?php if ($model->entry->participation->canAddAll()) : ?>
        <?= $form->field($model, 'forceJoin')->checkbox() ?>
    <?php endif; ?>
<?= Html::endTag('div') ?>