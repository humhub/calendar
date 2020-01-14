<?php

use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\modules\topic\widgets\TopicPicker;
use humhub\modules\ui\form\widgets\ColorPicker;
use humhub\modules\content\widgets\ContentTagDropDown;
use humhub\modules\ui\form\widgets\TimePicker;
use humhub\widgets\ActiveForm;
use humhub\widgets\TimeZoneDropdownAddition;
use yii\jui\DatePicker;

/* @var $form ActiveForm */
/* @var $calendarEntryForm CalendarEntryForm */
/* @var $contentContainer ContentContainerActiveRecord */
?>

<div class="modal-body">


    <div id="event-color-field" class="form-group space-color-chooser-edit" style="margin-top: 5px;">
        <?= $form->field($calendarEntryForm->entry, 'color')->widget(ColorPicker::class, ['container' => 'event-color-field'])->label(false) ?>

        <?= $form->field($calendarEntryForm->entry, 'title', ['template' => '
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i></i>
                                    </span>
                                    {input}
                                </div>
                                {error}{hint}'
        ])->textInput(['placeholder' => Yii::t('CalendarModule.views_entry_edit', 'Title'), 'maxlength' => 200])->label(false) ?>
    </div>

    <?php Yii::$app->formatter->timeZone = $calendarEntryForm->timeZone ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($calendarEntryForm, 'start_date')->widget(DatePicker::class, ['dateFormat' => Yii::$app->formatter->dateInputFormat, 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>
        </div>
        <div class="col-md-6 timeField" <?= !$calendarEntryForm->showTimeFields() ? 'style="opacity:0.2"' : '' ?>>
            <?= $form->field($calendarEntryForm, 'start_time')->widget(TimePicker::class, ['disabled' => $calendarEntryForm->entry->all_day]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($calendarEntryForm, 'end_date')->widget(DatePicker::class, ['dateFormat' => Yii::$app->formatter->dateInputFormat, 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>
        </div>
        <div class="col-md-6 timeField" <?= !$calendarEntryForm->showTimeFields() ? 'style="opacity:0.2"' : '' ?>>
            <?= $form->field($calendarEntryForm, 'end_time')->widget(TimePicker::class, ['disabled' => $calendarEntryForm->entry->all_day]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6"></div>
        <div class="col-md-6 timeZoneField"<?= $calendarEntryForm->entry->all_day ? ' hidden' : '' ?>>
            <?= TimeZoneDropdownAddition::widget(['model' => $calendarEntryForm])?>
        </div>
    </div>

    <?php Yii::$app->i18n->autosetLocale(); ?>

    <?= $form->field($calendarEntryForm->entry, 'all_day')->checkbox(['data-action-change' => 'toggleDateTime']) ?>
    <?= $form->field($calendarEntryForm, 'is_public')->checkbox() ?>

    <?= $form->field($calendarEntryForm->entry, 'description')->widget(RichTextField::class, ['placeholder' => Yii::t('CalendarModule.base', 'Description'), 'pluginOptions' => ['maxHeight' => '300px']])->label(false) ?>

    <?= $form->field($calendarEntryForm, 'type_id')->widget(ContentTagDropDown::class, [
        'tagClass' => CalendarEntryType::class,
        'contentContainer' => $contentContainer,
        'includeGlobal' => true,
        'prompt' => Yii::t('CalendarModule.views_entry_edit', 'Select event type...'),
        'options' => ['data-action-change' => 'changeEventType']
    ])->label(false) ?>

    <?= $form->field($calendarEntryForm, 'topics')->widget(TopicPicker::class, ['contentContainer' => $contentContainer]); ?>
</div>