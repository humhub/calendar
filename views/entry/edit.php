<?php
use humhub\modules\calendar\assets\CalendarBaseAssets;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\widgets\bootstrap\Tabs;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;
use yii\web\View;

/* @var $this View */
/* @var $calendarEntryForm CalendarEntryForm */
/* @var $contentContainer ContentContainerActiveRecord */
/* @var $editUrl string */

CalendarBaseAssets::register($this);

if ($calendarEntryForm->entry->isNewRecord) {
    $header = Yii::t('CalendarModule.views', '<strong>Create</strong> Event');
    $saveButtonText = Yii::t('CalendarModule.views', 'Next');
} else {
    $header = Yii::t('CalendarModule.views', '<strong>Edit</strong> Event');
    $saveButtonText = null;
}

if (RecurrenceHelper::isRecurrent($calendarEntryForm->entry)) {
    $header = Yii::t('CalendarModule.views', '<strong>Edit</strong> recurring event');
}

$calendarEntryForm->entry->color = empty($calendarEntryForm->entry->color) ? $this->theme->variable('info') : $calendarEntryForm->entry->color;

?>

<?php $form = Modal::beginFormDialog([
    'header' => $header,
    'size' => 'large',
    'closable' => false,
    'footer' => ModalButton::cancel() . ModalButton::save($saveButtonText, $editUrl),
    'form' => ['enableClientValidation' => false],
]) ?>
<div id="calendar-entry-form" data-ui-widget="calendar.Form" data-ui-init data-is-recurrent="<?= RecurrenceHelper::isRecurrent($calendarEntryForm->entry)?>">

    <?= $this->render('edit-recurrence-mode', ['form' => $form, 'model' => $calendarEntryForm->recurrenceForm]) ?>

    <div class="calendar-entry-form-tabs"<?= RecurrenceHelper::isRecurrentInstance($calendarEntryForm->entry) ? ' hidden' : ''  ?>>
        <?= Tabs::widget([
            'viewPath' => '@calendar/views/entry',
            'isSubMenu' => true,
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
            ],
        ]); ?>
    </div>
</div>
<?php Modal::endFormDialog() ?>
