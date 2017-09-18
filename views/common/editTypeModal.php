<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\calendar\interfaces\CalendarItemType;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\widgets\ActiveForm;
use humhub\widgets\ColorPickerField;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;

/* @var $model CalendarEntryType|CalendarItemType */

if($model instanceof CalendarItemType) {
    $title = Yii::t('CalendarModule.views_container-config_typesConfig', '<strong>Edit</strong> calendar');
    $titleAttribute = 'title';
    $titleDisabled = true;
} else {
    $title = ($model->isNewRecord)
        ? Yii::t('CalendarModule.views_container-config_typesConfig', '<strong>Create</strong> new event type')
        : Yii::t('CalendarModule.views_container-config_typesConfig', '<strong>Edit</strong> event type');
    $titleAttribute = 'name';
    $titleDisabled = false;
}

?>

<?php ModalDialog::begin(['header' => Yii::t('CalendarModule.views_container-config_typesConfig', $title)]); ?>
    <?php $form = ActiveForm::begin()?>
        <div class="modal-body">
            <div id="event-type-color-field" class="form-group space-color-chooser-edit" style="margin-top: 5px;">
                <?= ColorPickerField::widget(['model' => $model, 'container' => 'event-type-color-field']); ?>
                <?= $form->field($model, $titleAttribute, ['template' => '
                                {label}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i></i>
                                    </span>
                                    {input}
                                </div>
                                {error}{hint}'
                ])->textInput(['disabled' => $titleDisabled, 'placeholder' => Yii::t('CalendarModule.config', 'Name'), 'maxlength' => 100])->label(false) ?>
            </div>
            <?php if($model instanceof CalendarItemType) : ?>
                <?= $form->field($model, 'enabled')->checkbox() ?>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <?= ModalButton::submitModal(); ?>
            <?= ModalButton::cancel(); ?>
        </div>
    <?php ActiveForm::end()?>
<?php ModalDialog::end() ?>
