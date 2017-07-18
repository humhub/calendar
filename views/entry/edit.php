<?php

use humhub\libs\Html;
use humhub\libs\TimezoneHelper;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\file\widgets\FilePreview;
use humhub\modules\file\widgets\UploadButton;
use humhub\widgets\ColorPickerField;
use humhub\widgets\MarkdownEditor;
use yii\jui\DatePicker;
use humhub\widgets\ActiveForm;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\widgets\ModalDialog;

/* @var $this \humhub\components\View */
/* @var $calendarEntryForm CalendarEntryForm */

\humhub\modules\calendar\assets\Assets::register($this);

$header = ($calendarEntryForm->entry->isNewRecord)
    ? Yii::t('CalendarModule.views_entry_edit', '<strong>Create</strong> event')
    : Yii::t('CalendarModule.views_entry_edit', '<strong>Edit</strong> event');

$calendarEntryForm->entry->color = empty($calendarEntryForm->entry->color) ? $this->theme->variable('info') : $calendarEntryForm->entry->color;

?>


<?php ModalDialog::begin(['header' => $header]) ?>
<?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>

    <br>
    <ul id="tabs" class="nav nav-tabs" data-tabs="tabs" style="padding:0px 10px">
        <li class="active">
            <a href="#calendar-entry-basic" class="tab-basic" data-toggle="tab"><?= Yii::t('CalendarModule.views_entry_edit', 'Basic'); ?></a>
        </li>
        <li>
            <a href="#calendar-entry-participation" class="tab-participation" data-toggle="tab"><?= Yii::t('CalendarModule.views_entry_edit', 'Participation'); ?></a>
        </li>
        <li>
            <a href="#calendar-entry-files" class="tab-files" data-toggle="tab"><?= Yii::t('CalendarModule.views_entry_edit', 'Files'); ?></a>
        </li>
    </ul>

    <div id="calendar-entry-form" class="modal-body" data-ui-widget="calendar.Form" data-ui-init style="padding-top:0px">

        <?php if ($createFromGlobalCalendar): ?>
            <p><?= Yii::t('CalendarModule.views_entry_edit', '<strong>Note:</strong> This event will be created on your profile. To create a space event open the calendar on the desired space.'); ?></p>
        <?php endif; ?>

        <br/>

        <div class="tab-content">
            <div class="tab-pane active" id="calendar-entry-basic">

                <div id="event-color-field" class="form-group space-color-chooser-edit" style="margin-top: 5px;">
                    <?= ColorPickerField::widget(['model' => $calendarEntryForm->entry, 'field' => 'color', 'container' => 'event-color-field']); ?>

                    <?= $form->field($calendarEntryForm->entry, 'title', ['template' => '
                            {label}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i></i>
                                </span>
                                {input}
                            </div>
                            {error}{hint}'
                    ])->textInput(['placeholder' => Yii::t('CalendarModule.views_entry_edit', 'Title'), 'maxlength' => 45])->label(false) ?>
                </div>

                <?= $form->field($calendarEntryForm->entry, 'description')->textarea(['id' => 'calendar-entry-description', 'rows' => '3', 'placeholder' => Yii::t('CalendarModule.views_entry_edit', 'Description')])->label(false) ?>
                <?= MarkdownEditor::widget(['fieldId' => 'calendar-entry-description']); ?>
                <?= $form->field($calendarEntryForm, 'is_public')->checkbox() ?>
                <?= $form->field($calendarEntryForm->entry, 'all_day')->checkbox(['data-action-change' => 'toggleDateTime']) ?>

                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($calendarEntryForm, 'start_date')->widget(DatePicker::className(), ['dateFormat' => Yii::$app->params['formatter']['defaultDateFormat'], 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>
                    </div>
                    <div class="col-md-6 timeField" <?= !$calendarEntryForm->showTimeFields() ? 'style="opacity:0.5"' : '' ?>>
                        <?= $form->field($calendarEntryForm, 'start_time')->textInput(['placeholder' => '00:00', 'disabled' => !$calendarEntryForm->showTimeFields()]); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($calendarEntryForm, 'end_date')->widget(DatePicker::className(), ['dateFormat' => Yii::$app->params['formatter']['defaultDateFormat'], 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>
                    </div>
                    <div class="col-md-6 timeField" <?= !$calendarEntryForm->showTimeFields() ? 'style="opacity:0.5"' : '' ?>>
                        <?= $form->field($calendarEntryForm, 'end_time')->textInput(['placeholder' => '23:59', 'disabled' => !$calendarEntryForm->showTimeFields()]); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6"></div>
                    <div class="col-md-6 timeZoneField" style="margin-top:-10px;">
                        <a href="#" class="calendar-timezone pull-right" data-action-click="toggleTimezoneInput" style="font-size: 11px;color:<?= $this->theme->variable('info'); ?>">
                            <?= $calendarEntryForm->getTimezoneLabel() ?>
                        </a>
                        <br/>
                        <div class="timeZoneInput" style="display:none">
                            <?= $form->field($calendarEntryForm, 'timeZone')->dropDownList($calendarEntryForm->getTimeZoneItems(), ['data-action-change' => 'changeTimezone', 'data-ui-select2' => '', 'style' => 'width:100%']) ?>
                        </div>
                    </div>
                </div>

            </div>
            <div class="tab-pane" id="calendar-entry-participation">
                <?= $form->field($calendarEntryForm->entry, 'participation_mode')
                    ->dropDownList(CalendarEntryForm::getParticipationModeItems(), ['data-action-change' => 'changeParticipationMode'])->label(false) ?>
                <div class="participationOnly" style="<?= $calendarEntryForm->entry->isParticipationAllowed() ? '' : 'display:none' ?>">
                    <?= $form->field($calendarEntryForm->entry, 'allow_decline')->checkbox() ?>
                    <?= $form->field($calendarEntryForm->entry, 'allow_maybe')->checkbox() ?>
                    <?= $form->field($calendarEntryForm->entry, 'participant_info')
                        ->textarea(['id' => 'calendar-entry-participant-info', 'rows' => '3', 'placeholder' => Yii::t('CalendarModule.views_entry_edit', 'Participant Information')])->label(false) ?>
                    <?= MarkdownEditor::widget(['fieldId' => 'calendar-entry-participant-info']); ?>
                </div>
            </div>
            <div class="tab-pane" id="calendar-entry-files">
                <div class="row">
                    <div class="col-md-2">
                        <?= UploadButton::widget([
                            'id' => 'calendar_upload_button',
                            'label' => true,
                            'tooltip' => false,
                            'cssButtonClass' => 'btn-default btn-sm',
                            'model' => $calendarEntryForm->entry,
                            'dropZone' => '#calendar-entry-form',
                            'preview' => '#calendar_upload_preview',
                            'progress' => '#calendar_upload_progress',
                            'max' => Yii::$app->getModule('content')->maxAttachedFiles,
                        ]) ?>
                    </div>
                    <div class="col-md-1"></div>
                    <div class="col-md-9">
                        <?= FilePreview::widget([
                            'id' => 'calendar_upload_preview',
                            'options' => ['style' => 'margin-top:10px'],
                            'model' => $calendarEntryForm->entry,
                            'edit' => true,
                        ]) ?>

                        <div id="calendar_upload_progress" style="display:none"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
<hr>

    <div class="modal-footer">
        <button type="submit" class="btn btn-primary"
                data-action-click="ui.modal.submit"
                data-action-url="<?= $editUrl ?>"
                data-ui-loader>
            <?= Yii::t('base', 'Save') ?>
        </button>
        <button type="button" class="btn btn-default" data-dismiss="modal">
            <?= Yii::t('base', 'Close'); ?>
        </button>
    </div>
<?php ActiveForm::end(); ?>
<?php ModalDialog::end() ?>
