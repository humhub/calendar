<?php

use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\modules\topic\widgets\TopicPicker;
use humhub\modules\ui\form\widgets\ColorPicker;
use humhub\modules\content\widgets\ContentTagDropDown;
use humhub\modules\ui\form\widgets\TimePicker;
use humhub\widgets\TimeZoneDropdownAddition;
use yii\jui\DatePicker;

/* @var $form \humhub\widgets\ActiveForm */
/* @var $calendarEntryForm \humhub\modules\calendar\models\forms\CalendarEntryForm */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
?>

<div class="modal-body">

    <?= $this->render('@calendar/views/common/_reminder_config', ['reminders' => $calendarEntryForm->reminderSettings->reminders, 'form' => $form])?>

</div>