<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\forms\InviteForm;
use humhub\modules\user\widgets\UserPickerField;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
use yii\bootstrap\ActiveForm;

/* @var InviteForm $inviteForm */
?>
<?php ModalDialog::begin([
    'header' => Yii::t('CalendarModule.views_entry_view', '<strong>Invite</strong> participants'),
    'closable' => true,
]); ?>
<?php $form = ActiveForm::begin() ?>

<div class="modal-body">
    <?= $form->field($inviteForm, 'entryId')->hiddenInput()->label(false) ?>
    <?= UserPickerField::widget([
        'model' => $inviteForm,
        'form' => $form,
        'attribute' => 'userGuids',
        'placeholder' => Yii::t('AdminModule.user', 'Invite new participants...'),
        'focus' => true,
        'options' => ['label' => false],
    ]) ?>
</div>

<div class="modal-footer">
    <?= ModalButton::submitModal(null, Yii::t('AdminModule.user', 'Invite')) ?>
    <?= ModalButton::cancel() ?>
</div>

<?php ActiveForm::end(); ?>
<?php ModalDialog::end(); ?>
