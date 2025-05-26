<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\helpers\Html;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/* @var $contentContainerSelection array */
/* @var $canSelectProfileCalendar bool */
/* @var $submitUrl array */
?>
<?php Modal::beginFormDialog([
    'header' => Yii::t('CalendarModule.base', '<strong>Choose</strong> target calendar'),
    'size' => Modal::SIZE_DEFAULT,
    'footer' => $contentContainerSelection
        ? ModalButton::save(Yii::t('CalendarModule.base', 'Next'), $submitUrl)
        : ModalButton::cancel(Yii::t('base', 'Close')),
]) ?>
<?php if ($contentContainerSelection): ?>
    <?= Html::dropDownList('contentContainerId', null, $contentContainerSelection, [
        'class' => 'form-control',
        'data-ui-select2' => '',
        'prompt' => $canSelectProfileCalendar ?
            Yii::t('CalendarModule.base', 'Select calendar...') :
            Yii::t('CalendarModule.base', 'Select space...'),
    ]) ?>
<?php else: ?>
    <div class="alert alert-danger">
        <strong>
            <?= Yii::t('CalendarModule.base', 'Before a target calendar can be selected, the module must be activated in at least one Space.') ?>
        </strong>
    </div>
<?php endif; ?>
<?php Modal::endFormDialog() ?>
