<?php

use yii\jui\DatePicker;
use yii\widgets\ActiveForm;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\widgets\ModalDialog;

\humhub\modules\calendar\assets\Assets::register($this);

$header = ($calendarEntry->isNewRecord) ? Yii::t('CalendarModule.views_entry_edit', '<strong>Create</strong> event') :
        Yii::t('CalendarModule.views_entry_edit', '<strong>Edit</strong> event');
?>


<?php ModalDialog::begin(['header' => $header]) ?>
    <?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>
            <div class="modal-body">

                <?php if ($createFromGlobalCalendar): ?>
                    <p><?= Yii::t('CalendarModule.views_entry_edit', '<strong>Note:</strong> This event will be created on your profile. To create a space event open the calendar on the desired space.'); ?></p>
                <?php endif; ?>

                    
                <?php
                    if($calendarEntry->color === null) {
                        $calendarEntry->color = $this->theme->variable('info');
                    }
                ?>

                <div id="event-color-field" class="form-group space-color-chooser-edit" style="margin-top: 5px;">
                    <?= humhub\widgets\ColorPickerField::widget(['model' => $calendarEntry, 'field' => 'color', 'container' => 'event-color-field']); ?>

                    <?= $form->field($calendarEntry, 'title', ['template' => '
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
                    
                <?= $form->field($calendarEntry, 'description')->textarea(['rows' => '3', 'placeholder' => Yii::t('CalendarModule.views_entry_edit', 'Description')]) ?>   
                <?= $form->field($calendarEntry, 'is_public')->checkbox() ?>   
                <?= $form->field($calendarEntry, 'all_day')->checkbox(['id' => 'allDayCheckbox']) ?>   

                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($calendarEntry, 'start_datetime')->widget(DatePicker::className(), ['dateFormat' => Yii::$app->params['formatter']['defaultDateFormat'], 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>   
                    </div>
                    <div class="col-md-6 timeField" >
                        <?= $form->field($calendarEntry, 'start_time')->textInput(['placeholder' => 'hh:mm']); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($calendarEntry, 'end_datetime')->widget(DatePicker::className(), ['dateFormat' => Yii::$app->params['formatter']['defaultDateFormat'], 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>   
                    </div>
                    <div class="col-md-6 timeField">
                        <?= $form->field($calendarEntry, 'end_time')->textInput(['placeholder' => 'hh:mm']); ?>
                    </div>
                </div>

                <?php
                $modes = [
                    CalendarEntry::PARTICIPATION_MODE_NONE => Yii::t('CalendarModule.views_entry_edit', 'No participants'),
                    //CalendarEntry::PARTICIPATION_MODE_INVITE => Yii::t('CalendarModule.base', 'Select participants'),
                    CalendarEntry::PARTICIPATION_MODE_ALL => Yii::t('CalendarModule.views_entry_edit', 'Everybody can participate')
                ];
                ?>

                <?= $form->field($calendarEntry, 'participation_mode')->dropDownList($modes, ['id' => 'participation_mode', 'class' => 'form-control', 'placeholder' => Yii::t('CalendarModule.views_entry_edit', 'End Date/Time')])?>
                <?php // $form->field($calendarEntry, 'selected_participants')->textInput($modes, ['class' => 'form-control', 'placeholder' => Yii::t('CalendarModule.views_entry_edit', 'Participants')])?>
            </div>

            <div class="modal-footer">
                        <button type="submit" class="btn btn-primary"
                                data-action-click="ui.modal.submit"  
                                data-action-url="<?= $contentContainer->createUrl('/calendar/entry/edit', ['id' => $calendarEntry->id]) ?>" data-ui-loader>
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

<script type="text/javascript">
    $("#calendarentry-start_time").format({type: "daytime"});
    $("#calendarentry-end_time").format({type: "daytime"});


    $("#allDayCheckbox").change(function () {
        if ($(this).prop('checked')) {
            $(".timeField").hide();
        } else {
            $(".timeField").show();
        }
    });

    if ($("#allDayCheckbox").prop('checked')) {
        $(".timeField").hide();
    } else {
        $(".timeField").show();
    }


    $("#participation_mode").change(function () {
        if ($("#participation_mode").val() == <?= CalendarEntry::PARTICIPATION_MODE_INVITE; ?>) {
            $("#selectedUsersField").show();
        } else {
            $("#selectedUsersField").hide();
        }
    });
    if ($("#participation_mode").val() != <?= CalendarEntry::PARTICIPATION_MODE_INVITE; ?>) {
        $("#selectedUsersField").hide();
    }

    // set focus to input for space name
    $('#CalendarEntry_title').focus();

    // Shake modal after wrong validation
<?php if ($calendarEntry->hasErrors()) { ?>
        $('.modal-dialog').removeClass('fadeIn');
        $('.modal-dialog').addClass('shake');
<?php } ?>

</script>
