<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
use yii\bootstrap\ActiveForm;
use humhub\libs\Html;

/* @var $contentContainerSelection array */
/* @var $submitUrl array */

?>

<?php ModalDialog::begin(['header' => Yii::t('CalendarModule.base', '<strong>Choose</strong> target calendar'), 'size' => 'small'])?>
    <?php $form = ActiveForm::begin()?>
        <div class="modal-body">
            <?= Html::dropDownList('contentContainerId', null, $contentContainerSelection, ['class' => 'form-control', 'data-ui-select2' => ''])?>
        </div>
        <div class="modal-footer">
            <?= ModalButton::submitModal($submitUrl, Yii::t('CalendarModule.base', 'Next'))?>
            <?= ModalButton::cancel()?>
        </div>
    <?php $form = ActiveForm::end()?>
<?php ModalDialog::end() ?>
