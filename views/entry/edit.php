<?php


use humhub\modules\calendar\assets\Assets;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\models\recurrence\RecurrenceHelper;
use humhub\modules\calendar\Module;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use humhub\widgets\Tabs;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\widgets\ModalDialog;

/* @var $this \humhub\components\View */
/* @var $calendarEntryForm CalendarEntryForm */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */

Assets::register($this);

$header = ($calendarEntryForm->entry->isNewRecord)
    ? Yii::t('CalendarModule.views_entry_edit', '<strong>Create</strong> event')
    : Yii::t('CalendarModule.views_entry_edit', '<strong>Edit</strong> event');

if(RecurrenceHelper::isRecurrent($calendarEntryForm->entry)) {
    $header = Yii::t('CalendarModule.views_entry_edit', '<strong>Edit</strong> recurring event');
}

$calendarEntryForm->entry->color = empty($calendarEntryForm->entry->color) ? $this->theme->variable('info') : $calendarEntryForm->entry->color;

?>


<?php ModalDialog::begin(['header' => $header, 'closable' => false]) ?>
    <?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>

        <div id="calendar-entry-form" data-ui-widget="calendar.Form" data-ui-init data-is-recurrent="<?= RecurrenceHelper::isRecurrent($calendarEntryForm->entry)?>">

            <div class="calendar-entry-form-tabs">
                <?= Tabs::widget([
                    'viewPath' => '@calendar/views/entry',
                    'params' => ['form' => $form, 'calendarEntryForm' => $calendarEntryForm, 'contentContainer' => $contentContainer],
                    'items' => [
                        ['label' => Yii::t('CalendarModule.views_entry_edit', 'Basic'),'view' => 'edit-basic', 'linkOptions' => ['class' => 'tab-basic']],
                        ['label' => Yii::t('CalendarModule.views_entry_edit', 'Participation'),'view' => 'edit-participation', 'linkOptions' => ['class' => 'tab-participation']],
                        ['label' => Yii::t('CalendarModule.views_entry_edit', 'Reminder'),'view' => 'edit-reminder', 'linkOptions' => ['class' => 'tab-reminder']],
                        ['label' => Yii::t('CalendarModule.views_entry_edit', 'Recurrence'),'view' => 'edit-recurrence', 'linkOptions' => ['class' => 'tab-recurrence'], 'visible' => Module::isRecurrenceActive()],
                        ['label' => Yii::t('CalendarModule.views_entry_edit', 'Files'), 'view' => 'edit-files', 'linkOptions' => ['class' => 'tab-files']]
                     ]
                ]); ?>
            </div>

            <div class="recurrence-edit-type" style="display: none">

                <?= $form->field($calendarEntryForm->recurrenceForm, 'recurrenceEditMode')->hiddenInput(['id' => 'recurrenceEditMode'])->label(false) ?>

                <br>
                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md-6">
                        <?= Button::primary(Yii::t('CalendarModule.recurrence', 'Update this event'))
                            ->action('submit', $editUrl)->options(['data-edit-mode' => RecurrenceFormModel::RECUR_EDIT_MODE_THIS ])->style('width:100%')?>
                    </div>
                    <div class="col-md-3"></div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md-6">
                        <?= Button::primary(Yii::t('CalendarModule.recurrence', 'Update this and following events'))
                            ->action('submit', $editUrl)->options(['data-edit-mode' => RecurrenceFormModel::RECUR_EDIT_MODE_FOLLOWING ])->style('width:100%')?>
                    </div>
                    <div class="col-md-3"></div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md-6">
                        <?= Button::primary(Yii::t('CalendarModule.recurrence', 'Update all events'))
                            ->action('submit', $editUrl)->options(['data-edit-mode' => RecurrenceFormModel::RECUR_EDIT_MODE_ALL ])->style('width:100%') ?>
                    </div>
                    <div class="col-md-3"></div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-3"></div>
                    <div class="col-md-6">
                        <?= ModalButton::cancel()->style('width:100%'); ?>
                    </div>
                    <div class="col-md-3"></div>
                </div>
                <br>
            </div>

            <hr>

            <div class="modal-footer">
                <?= Button::save()->action('submit', $editUrl); ?>
                <?= ModalButton::cancel(); ?>
            </div>

        </div>
    <?php ActiveForm::end(); ?>
<?php ModalDialog::end() ?>
