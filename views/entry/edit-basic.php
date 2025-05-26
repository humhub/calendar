<?php

use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\Module;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\widgets\ContentTagDropDown;
use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\modules\topic\widgets\TopicPicker;
use humhub\modules\ui\form\widgets\TimePicker;
use humhub\widgets\form\ActiveForm;
use humhub\widgets\form\ContentHiddenCheckbox;
use humhub\widgets\form\ContentVisibilitySelect;
use humhub\widgets\TimeZoneDropdownAddition;
use yii\jui\DatePicker;

/* @var $form ActiveForm */
/* @var $calendarEntryForm CalendarEntryForm */
/* @var $contentContainer ContentContainerActiveRecord */
?>
<div id="event-color-field" class="input-group mt-3 input-color-group">
    <?= $form->field($calendarEntryForm->entry, 'color')->colorInput() ?>
    <?= $form->field($calendarEntryForm->entry, 'title')
        ->textInput([
            'placeholder' => Yii::t('CalendarModule.views', 'Title'),
            'autofocus' => '',
        ]) ?>
</div>

<?php Yii::$app->formatter->timeZone = $calendarEntryForm->timeZone ?>

<div class="row">
    <div class="col-6">
        <?= $form->field($calendarEntryForm, 'start_date')->widget(DatePicker::class, ['dateFormat' => Yii::$app->formatter->dateInputFormat, 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>
    </div>
    <div class="col-6 timeField" <?= !$calendarEntryForm->showTimeFields() ? 'style="opacity:0.2"' : '' ?>>
        <?= $form->field($calendarEntryForm, 'start_time')->widget(TimePicker::class, ['disabled' => $calendarEntryForm->entry->all_day]); ?>
    </div>
</div>

<div class="row">
    <div class="col-6">
        <?= $form->field($calendarEntryForm, 'end_date')->widget(DatePicker::class, ['dateFormat' => Yii::$app->formatter->dateInputFormat, 'clientOptions' => [], 'options' => ['class' => 'form-control']]) ?>
    </div>
    <div class="col-6 timeField" <?= !$calendarEntryForm->showTimeFields() ? 'style="opacity:0.2"' : '' ?>>
        <?= $form->field($calendarEntryForm, 'end_time')->widget(TimePicker::class, ['disabled' => $calendarEntryForm->entry->all_day]); ?>
    </div>
</div>

<div class="row">
    <div class="col-6">
        <div class="row">
        <div class="col-md-6">
            <?= $form->field($calendarEntryForm->entry, 'all_day')->checkbox(['data-action-change' => 'toggleDateTime']) ?>
        </div>
        <?php if (Module::isRecurrenceActive()) : ?>
            <div class="col-md-6">
                <?= $form->field($calendarEntryForm, 'recurring')->checkbox(['data-action-change' => 'toggleRecurring']) ?>
            </div>
        <?php endif; ?>
        </div>
    </div>
    <div class="col-6 timeZoneField"<?= $calendarEntryForm->entry->all_day ? ' hidden' : '' ?>>
        <?= TimeZoneDropdownAddition::widget(['model' => $calendarEntryForm]) ?>
    </div>
</div>

<?php Yii::$app->i18n->autosetLocale(); ?>

<div class="row">
    <div class="col-md-6">
        <?= $form->field($calendarEntryForm->entry, 'location')->textInput() ?>
    </div>
</div>

<div class="row">
    <?php if ($calendarEntryForm->recurring || $calendarEntryForm->isFutureEvent()) : ?>
        <div class="col-md-3">
            <?= $form->field($calendarEntryForm, 'reminder')->checkbox(['data-action-change' => 'toggleReminder']) ?>
        </div>
    <?php endif; ?>
    <div class="col-md-3">
        <?= $form->field($calendarEntryForm, 'hidden')->widget(ContentHiddenCheckbox::class) ?>
    </div>
    <?php if ($calendarEntryForm->canCreatePublicEntry()) : ?>
        <div class="col-md-6">
            <?= $form->field($calendarEntryForm, 'is_public')->widget(ContentVisibilitySelect::class, ['contentOwner' => 'entry']) ?>
        </div>
    <?php endif; ?>
</div>

<?= $form->field($calendarEntryForm->entry, 'description')->widget(RichTextField::class, ['placeholder' => Yii::t('CalendarModule.base', 'Description'), 'pluginOptions' => ['maxHeight' => '300px']])->label(false) ?>

<?= $form->field($calendarEntryForm, 'type_id')->widget(ContentTagDropDown::class, [
    'tagClass' => CalendarEntryType::class,
    'contentContainer' => $contentContainer,
    'includeGlobal' => true,
    'prompt' => Yii::t('CalendarModule.views', 'Select event type...'),
    'options' => ['data-action-change' => 'changeEventType']
])->label(false) ?>

<?= $form->field($calendarEntryForm, 'topics')->widget(TopicPicker::class, ['contentContainer' => $contentContainer]); ?>

<?php if (!$calendarEntryForm->entry->isNewRecord) : ?>
    <?= $form->field($calendarEntryForm, 'sendUpdateNotification')->checkbox() ?>
<?php endif; ?>
