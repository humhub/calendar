<?php

use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\ui\form\widgets\ActiveForm;

/* @var $form ActiveForm */
/* @var $calendarEntryForm CalendarEntryForm */
/* @var $contentContainer ContentContainerActiveRecord */

?>

<div class="modal-body">

    <?= $this->render('@calendar/views/common/_reminder_config', ['settings' => $calendarEntryForm->reminderSettings, 'form' => $form])?>

</div>