<?php
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\topic\widgets\TopicPicker;
use humhub\modules\ui\form\widgets\ColorPicker;
use humhub\modules\content\widgets\ContentTagDropDown;
use humhub\modules\ui\form\widgets\Markdown;
use humhub\modules\ui\form\widgets\TimePicker;
use humhub\widgets\TimeZoneDropdownAddition;
use yii\jui\DatePicker;

/* @var $form \humhub\widgets\ActiveForm */
/* @var $calendarEntryForm \humhub\modules\calendar\models\forms\CalendarEntryForm */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
?>

<div class="modal-body">


    <div id="event-color-field" class="form-group space-color-chooser-edit" style="margin-top: 5px;">
        <?= $form->field($calendarEntryForm->entry, 'color')->widget(ColorPicker::class, ['container' => 'event-color-field']) ?>

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

    <?= $form->field($calendarEntryForm, 'type_id')->widget(ContentTagDropDown::class, [
        'tagClass' => CalendarEntryType::class,
        'contentContainer' => $contentContainer,
        'includeGlobal' => true,
        'prompt' => Yii::t('CalendarModule.views_entry_edit', 'Select event type...'),
        'options' => ['data-action-change' => 'changeEventType']
    ])->label(false); ?>

    <?= $form->field($calendarEntryForm->entry, 'description')->widget(Markdown::class, ['fileModel' => $calendarEntryForm->entry, 'fileAttribute' => 'files'])->label(false) ?>

    <?= $form->field($calendarEntryForm, 'is_public')->checkbox() ?>
    <?= $form->field($calendarEntryForm->entry, 'all_day')->checkbox(['data-action-change' => 'toggleDateTime']) ?>

    <?php Yii::$app->formatter->timeZone = $calendarEntryForm->timeZone ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($calendarEntryForm, 'start_date')->widget(DatePicker::class, ['dateFormat' => Yii::$app->params['formatter']['defaultDateFormat'], 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>
        </div>
        <div class="col-md-6 timeField" <?= !$calendarEntryForm->showTimeFields() ? 'style="opacity:0.2"' : '' ?>>
            <?= $form->field($calendarEntryForm, 'start_time')->widget(TimePicker::class, ['disabled' => $calendarEntryForm->entry->all_day]); ?>
        </div>
    </div>



    <div class="row">
        <div class="col-md-6">
            <?= $form->field($calendarEntryForm, 'end_date')->widget(DatePicker::class, ['dateFormat' => Yii::$app->params['formatter']['defaultDateFormat'], 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>
        </div>
        <div class="col-md-6 timeField" <?= !$calendarEntryForm->showTimeFields() ? 'style="opacity:0.2"' : '' ?>>
            <?= $form->field($calendarEntryForm, 'end_time')->widget(TimePicker::class, ['disabled' => $calendarEntryForm->entry->all_day]); ?>
        </div>
    </div>

    <?php Yii::$app->i18n->autosetLocale(); ?>

    <div class="row">
        <div class="col-md-6"></div>
        <div class="col-md-6 timeZoneField" >
            <?= TimeZoneDropdownAddition::widget(['model' => $calendarEntryForm])?>
        </div>
    </div>

    <?= $form->field($calendarEntryForm, 'topics')->widget(TopicPicker::class, ['contentContainer' => $contentContainer]); ?>
</div>