<?php

use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\widgets\form\ActiveForm;

/* @var $form ActiveForm */
/* @var $calendarEntryForm CalendarEntryForm */
/* @var $contentContainer ContentContainerActiveRecord */
?>
<div class="form-text my-3">
    <?= Yii::t('CalendarModule.base', 'Here you can configure the default reminder settings for this event. Users are able to overwrite these settings by means of the<br/><strong>Set reminder</strong> link.')?>
</div>

<?= $this->render('@calendar/views/common/_reminder_config', ['settings' => $calendarEntryForm->reminderSettings, 'form' => $form]) ?>
