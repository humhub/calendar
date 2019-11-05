<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
use humhub\modules\ui\form\widgets\ActiveForm;

/* @var \humhub\modules\calendar\models\forms\ReminderSettings $reminderSettings */

?>

<?php ModalDialog::begin(['header' => Yii::t('CalendarModule.base', '<strong>Set</strong> reminder')]) ?>

    <?php $form = ActiveForm::begin() ?>
        <div class="modal-body">

            <?= $this->render('@calendar/views/common/_reminder_config', ['reminders' => $reminderSettings->reminder, 'form' => $form])?>


        </div>
        <div class="modal-footer">
            <?= ModalButton::submitModal() ?>
            <?= ModalButton::cancel() ?>
        </div>
    <?php ActiveForm::end() ?>

<?php ModalDialog::end() ?>
