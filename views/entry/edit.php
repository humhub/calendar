<?php
use humhub\modules\calendar\assets\CalendarBaseAssets;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\widgets\ModalButton;
use humhub\widgets\Tabs;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\widgets\ModalDialog;

/* @var $this \humhub\modules\ui\view\components\View */
/* @var $calendarEntryForm CalendarEntryForm */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
/* @var $editUrl string */

CalendarBaseAssets::register($this);

if ($calendarEntryForm->entry->isNewRecord) {
    $header = Yii::t('CalendarModule.views', '<strong>Create</strong> Event');
    $saveButtonText = Yii::t('CalendarModule.views', 'Next');
} else {
    $header = Yii::t('CalendarModule.views', '<strong>Edit</strong> Event');
    $saveButtonText = null;
}

if(RecurrenceHelper::isRecurrent($calendarEntryForm->entry)) {
    $header = Yii::t('CalendarModule.views', '<strong>Edit</strong> recurring event');
}

$calendarEntryForm->entry->color = empty($calendarEntryForm->entry->color) ? $this->theme->variable('info') : $calendarEntryForm->entry->color;

?>


<?php ModalDialog::begin(['header' => $header, 'size' => 'large', 'closable' => false]) ?>
    <?php $form = ActiveForm::begin(['enableClientValidation' => false]); ?>

        <div id="calendar-entry-form" data-ui-widget="calendar.Form" data-ui-init data-is-recurrent="<?= RecurrenceHelper::isRecurrent($calendarEntryForm->entry)?>">

            <?= $this->render('edit-recurrence-mode', ['form' => $form, 'model' => $calendarEntryForm->recurrenceForm]) ?>

            <div class="calendar-entry-form-tabs"<?= RecurrenceHelper::isRecurrentInstance($calendarEntryForm->entry) ? ' hidden' : ''  ?>>
                <?= Tabs::widget([
                    'viewPath' => '@calendar/views/entry',
                    'params' => ['form' => $form, 'calendarEntryForm' => $calendarEntryForm, 'contentContainer' => $contentContainer],
                    'items' => [
                        [
                            'label' => Yii::t('CalendarModule.views', 'General'),
                            'view' => 'edit-basic',
                            'linkOptions' => ['class' => 'tab-basic'],
                        ],
                        [
                            'label' => Yii::t('CalendarModule.views', 'Reminder'),
                            'view' => 'edit-reminder',
                            'linkOptions' => ['class' => 'tab-reminder'],
                            'headerOptions' => $calendarEntryForm->showReminderTab() ? [] : ['style' => 'display:none'],
                        ],
                        [
                            'label' => Yii::t('CalendarModule.views', 'Recurrence'),
                            'view' => 'edit-recurrence',
                            'linkOptions' => ['class' => 'tab-recurrence'],
                            'headerOptions' => $calendarEntryForm->showRecurrenceTab() ? [] : ['style' => 'display:none'],
                        ],
                    ]
                ]); ?>
            </div>

            <hr>

            <div class="modal-footer">
                <?= ModalButton::cancel(); ?>
                <?= ModalButton::submitModal($editUrl, $saveButtonText); ?>
            </div>
        </div>
    <?php ActiveForm::end(); ?>
<?php ModalDialog::end() ?>
