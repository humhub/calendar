<?php

use humhub\libs\TimezoneHelper;
use yii\jui\DatePicker;
use yii\widgets\ActiveForm;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\widgets\ModalDialog;

/* @var $this \humhub\components\View */
/* @var $calendarEntryForm \humhub\modules\calendar\models\forms\CalendarEntryForm */

\humhub\modules\calendar\assets\Assets::register($this);

$header = ($calendarEntryForm->entry->isNewRecord)
    ? Yii::t('CalendarModule.views_entry_edit', '<strong>Create</strong> event')
    : Yii::t('CalendarModule.views_entry_edit', '<strong>Edit</strong> event');

$calendarEntryForm->entry->color = empty($calendarEntryForm->entry->color) ?  $this->theme->variable('info') : $calendarEntryForm->entry->color;

?>


<?php ModalDialog::begin(['header' => $header]) ?>
    <?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>
            <div class="modal-body" data-ui-widget="calendar.Form" data-ui-init>

                <?php if ($createFromGlobalCalendar): ?>
                    <p><?= Yii::t('CalendarModule.views_entry_edit', '<strong>Note:</strong> This event will be created on your profile. To create a space event open the calendar on the desired space.'); ?></p>
                <?php endif; ?>


                <div id="event-color-field" class="form-group space-color-chooser-edit" style="margin-top: 5px;">
                    <?= humhub\widgets\ColorPickerField::widget(['model' => $calendarEntryForm->entry, 'field' => 'color', 'container' => 'event-color-field']); ?>

                    <?= $form->field($calendarEntryForm->entry, 'title', ['template' => '
                        {label}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i></i>
                            </span>
                            {input}
                        </div>
                        {error}{hint}'
                        ])->textInput(['placeholder' => Yii::t('CalendarModule.views_entry_edit', 'Title'), 'maxlength' => 45 ])->label(false) ?>
                </div>
                    
                <?= $form->field($calendarEntryForm->entry, 'description')->textarea(['rows' => '3', 'placeholder' => Yii::t('CalendarModule.views_entry_edit', 'Description')]) ?>
                <?= $form->field($calendarEntryForm, 'is_public')->checkbox() ?>
                <?= $form->field($calendarEntryForm->entry, 'all_day')->checkbox(['data-action-change' => 'toggleDateTime']) ?>

                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($calendarEntryForm, 'start_date')->widget(DatePicker::className(), ['dateFormat' => Yii::$app->params['formatter']['defaultDateFormat'], 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>
                    </div>
                    <div class="col-md-6 timeField" style="<?= (!$calendarEntryForm->showTimeFields() ? 'display:none' : '') ?>">
                        <?= $form->field($calendarEntryForm, 'start_time')->textInput(['placeholder' => 'hh:mm']); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($calendarEntryForm, 'end_date')->widget(DatePicker::className(), ['dateFormat' => Yii::$app->params['formatter']['defaultDateFormat'], 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>
                    </div>
                    <div class="col-md-6 timeField" style="<?= (!$calendarEntryForm->showTimeFields() ? 'display:none' : '') ?>">
                        <?= $form->field($calendarEntryForm, 'end_time')->textInput(['placeholder' => 'hh:mm']); ?>

                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6"></div>
                    <div class="col-md-6 timeField" style="margin-top:-10px;<?= (!$calendarEntryForm->showTimeFields() ? 'display:none' : '') ?>" >
                        <a href="#" class="calendar-timezone pull-right" data-action-click="toggleTimezoneInput" style="font-size: 11px;color:<?= $this->theme->variable('info'); ?>">
                            <?= $calendarEntryForm->getUserTimezoneLabel() ?>
                        </a>
                        <br/>
                        <div class="timeZoneInput" style="display:none">
                            <?= $form->field($calendarEntryForm, 'timeZone')->dropDownList($calendarEntryForm->getTimeZoneItems(), ['data-action-change' => 'changeTimezone', 'data-ui-select2' => '', 'style' => 'width:100%'])?>
                        </div>
                    </div>
                </div>

                <?= $form->field($calendarEntryForm->entry, 'participation_mode')->dropDownList($calendarEntryForm->getParticipationModeItems())?>
            </div>

            <div class="modal-footer">
                        <button type="submit" class="btn btn-primary"
                                data-action-click="ui.modal.submit"  
                                data-action-url="<?= $contentContainer->createUrl('/calendar/entry/edit', ['id' => $calendarEntryForm->entry->id]) ?>" data-ui-loader>
                                    <?= Yii::t('base', 'Save') ?>
                        </button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?= Yii::t('base', 'Close'); ?>
                        </button>
                    </div>
                </div>
            </div>
    <?php ActiveForm::end(); ?>
<?php ModalDialog::end() ?>
