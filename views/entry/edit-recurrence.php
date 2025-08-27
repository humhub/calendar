<?php

use humhub\modules\calendar\interfaces\recurrence\widgets\RecurrenceFormWidget;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\widgets\form\ActiveForm;

/* @var $form ActiveForm */
/* @var $calendarEntryForm CalendarEntryForm */
?>
<?= RecurrenceFormWidget::widget([
    'form' => $form,
    'model' => $calendarEntryForm->recurrenceForm,
    'picker' => '#calendarentryform-start_date'
]) ?>
