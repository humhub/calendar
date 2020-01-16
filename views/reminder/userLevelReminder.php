<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\libs\Html;
use humhub\modules\calendar\models\reminder\forms\ReminderSettings;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
use humhub\modules\ui\form\widgets\ActiveForm;

/* @var ReminderSettings $reminderSettings */

?>

<?php ModalDialog::begin(['header' => Yii::t('CalendarModule.base', '<strong>Reminder</strong> settings')]) ?>

    <?php $form = ActiveForm::begin() ?>
        <div class="modal-body">
            <div class="help-block">
                <?= Yii::t('CalendarModule.reminder', 'Your reminder settings for event: <strong>\'{title}\'</strong>', [
                        'title' => Html::encode($reminderSettings->entry->getTitle())
                ])?>
            </div>
            <hr>
            <?= $this->render('@calendar/views/common/_reminder_config', ['settings' => $reminderSettings, 'form' => $form])?>

        </div>
        <div class="modal-footer">
            <?= ModalButton::submitModal() ?>
            <?= ModalButton::cancel() ?>
        </div>
    <?php ActiveForm::end() ?>

<?php ModalDialog::end() ?>
