<?php

use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\ui\form\widgets\ActiveForm;

/* @var $form ActiveForm */
/* @var $calendarEntryForm CalendarEntryForm */
/* @var $contentContainer ContentContainerActiveRecord */

?>

<div class="modal-body">

    <div class="help-block">
        <?= Yii::t('CalendarModule.reminder', 'Here you can configure the default reminder settings for this event. Users are able to overwrite these settings by means of the
        <strong>Set reminder</strong> link.')?>
    </div>

    <?= $this->render('@calendar/views/common/_reminder_config', ['settings' => $calendarEntryForm->reminderSettings, 'form' => $form])?>

</div>