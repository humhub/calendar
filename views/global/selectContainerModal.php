<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\libs\Html;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;

/* @var $contentContainerSelection array */
/* @var $canSelectProfileCalendar bool */
/* @var $submitUrl array */

?>

<?php ModalDialog::begin(['header' => Yii::t('CalendarModule.base', '<strong>Choose</strong> target calendar'), 'size' => 'small']) ?>
<?php if ($contentContainerSelection): ?>
    <?php $form = ActiveForm::begin() ?>
    <div class="modal-body">
        <?= Html::dropDownList('contentContainerId', null, $contentContainerSelection, [
            'class' => 'form-control',
            'data-ui-select2' => '',
            'prompt' => $canSelectProfileCalendar ?
                Yii::t('CalendarModule.base', 'Select calendar...') :
                Yii::t('CalendarModule.base', 'Select space...'),
        ]) ?>
    </div>
    <div class="modal-footer">
        <?= ModalButton::submitModal($submitUrl, Yii::t('CalendarModule.base', 'Next')) ?>
    </div>
    <?php $form = ActiveForm::end() ?>

<?php else: ?>
    <div class="modal-body">
        <div class="alert alert-danger">
            <strong>
                <?= Yii::t('CalendarModule.base', 'Before a target calendar can be selected, the module must be activated in at least one Space.') ?>
            </strong>
        </div>
    </div>
    <div class="modal-footer">
        <?= ModalButton::cancel(Yii::t('base', 'Close')) ?>
    </div>
<?php endif; ?>
<?php ModalDialog::end() ?>
