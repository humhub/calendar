<?php

use yii\helpers\Html;
use yii\jui\DatePicker;
use humhub\compat\CActiveForm;
use humhub\modules\calendar\models\CalendarEntry;

?>


<?php $form = CActiveForm::begin(); ?>
<div class="modal-dialog modal-dialog-normal animated fadeIn">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="myModalLabel">
                <?php if (!$calendarEntry->isNewRecord) : ?>
                    <?php echo Yii::t('CalendarModule.views_entry_edit', '<strong>Edit</strong> event'); ?>
                <?php else: ?>
                    <?php echo Yii::t('CalendarModule.views_entry_edit', '<strong>Create</strong> event'); ?>
                <?php endif; ?>
            </h4>
        </div>
        <div class="modal-body">

            <?php if ($createFromGlobalCalendar): ?>
                <p><?php echo Yii::t('CalendarModule.views_entry_edit', '<strong>Note:</strong> This event will be created on your profile. To create a space event open the calendar on the desired space.'); ?></p>
            <?php endif; ?>

            <?php echo $form->errorSummary($calendarEntry); ?>


            <div class="form-group">
                <?php echo $form->labelEx($calendarEntry, 'title'); ?>
                <?php echo $form->textField($calendarEntry, 'title', array('class' => 'form-control', 'placeholder' => Yii::t('CalendarModule.views_entry_edit', 'Title'))); ?>
            </div>

            <div class="form-group">
                <?php echo $form->labelEx($calendarEntry, 'description'); ?>
                <?php echo $form->textArea($calendarEntry, 'description', array('class' => 'form-control', 'rows' => '3', 'placeholder' => Yii::t('CalendarModule.views_entry_edit', 'Description'))); ?>
            </div>
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <?php echo $form->checkBox($calendarEntry, 'is_public', array()); ?> <?php echo $calendarEntry->getAttributeLabel('is_public'); ?>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <?php echo $form->checkBox($calendarEntry, 'all_day', array('id' => 'allDayCheckbox')); ?> <?php echo $calendarEntry->getAttributeLabel('all_day'); ?>
                    </label>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?php echo $form->field($calendarEntry, 'start_datetime')->widget(DatePicker::className(), ['dateFormat' => Yii::$app->params['formatter']['defaultDateFormat'], 'clientOptions' => [], 'options' => ['class' => 'form-control']]); ?>
                </div>
                <div class="col-md-6">
                    <div class="timeFields">
                        <?php echo $form->field($calendarEntry, 'start_time')->textInput(['placeholder' => 'hh:mm']); ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div
                    class="col-md-6"><?php echo $form->field($calendarEntry, 'end_datetime')->widget(DatePicker::className(), ['dateFormat' => Yii::$app->params['formatter']['defaultDateFormat'], 'clientOptions' => [], 'options' => ['class' => 'form-control']]); ?></div>
                <div class="col-md-6">
                    <div class="timeFields">
                        <?php echo $form->field($calendarEntry, 'end_time')->textInput(['placeholder' => 'hh:mm']); ?>
                    </div>
                </div>
            </div>
            <hr>

            <div class="form-group">
                <?php
                $modes = array(
                    CalendarEntry::PARTICIPATION_MODE_NONE => Yii::t('CalendarModule.views_entry_edit', 'No participants'),
                    //CalendarEntry::PARTICIPATION_MODE_INVITE => Yii::t('CalendarModule.base', 'Select participants'),
                    CalendarEntry::PARTICIPATION_MODE_ALL => Yii::t('CalendarModule.views_entry_edit', 'Everybody can participate')
                );
                ?>
                <?php echo $form->labelEx($calendarEntry, 'participant_mode'); ?>
                <?php echo $form->dropDownList($calendarEntry, 'participation_mode', $modes, array('id' => 'participation_mode', 'class' => 'form-control', 'placeholder' => Yii::t('CalendarModule.views_entry_edit', 'End Date/Time')), array('pickTime' => true)); ?>
            </div>
            <div class="form-group" id="selectedUsersField">
                <?php echo $form->labelEx($calendarEntry, 'selected_participants'); ?>
                <?php echo $form->textField($calendarEntry, 'selected_participants', array('class' => 'form-control', 'placeholder' => Yii::t('CalendarModule.views_entry_edit', 'Participants'))); ?>
            </div>
        </div>

        <div class="modal-footer">
            <div class="row">
                <div class="col-md-8 text-left">
                    <?php
                    echo \humhub\widgets\AjaxButton::widget([
                        'label' => Yii::t('CalendarModule.views_entry_edit', 'Save'),
                        'ajaxOptions' => [
                            'type' => 'POST',
                            'beforeSend' => new yii\web\JsExpression('function(){ setModalLoader(); }'),
                            'success' => new yii\web\JsExpression('function(html){ $("#globalModal").html(html); }'),
                            'url' => $contentContainer->createUrl('/calendar/entry/edit', ['id' => $calendarEntry->id]),
                        ],
                        'htmlOptions' => [
                            'class' => 'btn btn-primary'
                        ]
                    ]);
                    ?>
                    <button type="button" class="btn btn-primary"
                            data-dismiss="modal"><?php echo Yii::t('CalendarModule.views_entry_edit', 'Close'); ?></button>
                </div>
                <div class="col-md-4 text-right">
                    <?php
                    if (!$calendarEntry->isNewRecord) {
                        echo Html::a(Yii::t('CalendarModule.views_entry_edit', 'Delete'), $contentContainer->createUrl('//calendar/entry/delete', array('id' => $calendarEntry->id)), array('class' => 'btn btn-danger'));
                    }
                    ?>

                </div>
            </div>



            <div id="event-loader" class="loader loader-modal hidden">
                <div class="sk-spinner sk-spinner-three-bounce">
                    <div class="sk-bounce1"></div>
                    <div class="sk-bounce2"></div>
                    <div class="sk-bounce3"></div>
                </div>
            </div>

        </div>


    </div>
</div>

<script type="text/javascript">
    $("#calendarentry-start_time").format({type: "daytime"});
    $("#calendarentry-end_time").format({type: "daytime"});


    $("#allDayCheckbox").change(function () {
        if ($("#allDayCheckbox").prop('checked')) {
            $(".timeFields").hide();
        } else {
            $(".timeFields").show();
        }
    });

    if ($("#allDayCheckbox").prop('checked')) {
        $(".timeFields").hide();
    } else {
        $(".timeFields").show();
    }


    $("#participation_mode").change(function () {
        if ($("#participation_mode").val() == <?php echo CalendarEntry::PARTICIPATION_MODE_INVITE; ?>) {
            $("#selectedUsersField").show();
        } else {
            $("#selectedUsersField").hide();
        }
    });
    if ($("#participation_mode").val() != <?php echo CalendarEntry::PARTICIPATION_MODE_INVITE; ?>) {
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


<?php CActiveForm::end(); ?>
