<?php

use humhub\libs\Html;
use humhub\libs\TimezoneHelper;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\file\widgets\FilePreview;
use humhub\modules\file\widgets\UploadButton;
use humhub\widgets\Button;
use humhub\widgets\ColorPickerField;
use humhub\widgets\MarkdownEditor;
use humhub\widgets\ModalButton;
use humhub\widgets\Tabs;
use yii\jui\DatePicker;
use humhub\widgets\ActiveForm;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\widgets\ModalDialog;

/* @var $this \humhub\components\View */
/* @var $calendarEntryForm CalendarEntryForm */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */

\humhub\modules\calendar\assets\Assets::register($this);

$header = ($calendarEntryForm->entry->isNewRecord)
    ? Yii::t('CalendarModule.views_entry_edit', '<strong>Create</strong> event')
    : Yii::t('CalendarModule.views_entry_edit', '<strong>Edit</strong> event');

$calendarEntryForm->entry->color = empty($calendarEntryForm->entry->color) ? $this->theme->variable('info') : $calendarEntryForm->entry->color;

?>


<?php ModalDialog::begin(['header' => $header, 'closable' => false]) ?>
    <?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>

        <div id="calendar-entry-form" data-ui-widget="calendar.Form" data-ui-init>

            <?= Tabs::widget([
                'viewPath' => '@calendar/views/entry',
                'params' => ['form' => $form, 'calendarEntryForm' => $calendarEntryForm, 'contentContainer' => $contentContainer],
                'items' => [
                    ['label' => Yii::t('CalendarModule.views_entry_edit', 'Basic'),'view' => 'edit-basic', 'linkOptions' => ['class' => 'tab-basic']],
                    ['label' => Yii::t('CalendarModule.views_entry_edit', 'Participation'),'view' => 'edit-participation', 'linkOptions' => ['class' => 'tab-participation']],
                    ['label' => Yii::t('CalendarModule.views_entry_edit', 'Files'),'view' => 'edit-files', 'linkOptions' => ['class' => 'tab-files']]
                 ]
            ]); ?>

        </div>

        <hr>

        <div class="modal-footer">
            <?= ModalButton::submitModal($editUrl); ?>
            <?= ModalButton::cancel(); ?>
        </div>
    <?php ActiveForm::end(); ?>
<?php ModalDialog::end() ?>
