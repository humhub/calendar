<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
use humhub\modules\file\widgets\FilePreview;
use humhub\modules\file\widgets\UploadButton;


/* @var $form \humhub\widgets\ActiveForm */
/* @var $calendarEntryForm \humhub\modules\calendar\models\forms\CalendarEntryForm */
?>

<div class="modal-body">
    <div class="row">
        <div class="col-md-2">
            <?= UploadButton::widget([
                'id' => 'calendar_upload_button',
                'label' => true,
                'tooltip' => false,
                'cssButtonClass' => 'btn-default btn-sm',
                'model' => $calendarEntryForm->entry,
                'attribute' => 'files',
                'dropZone' => '#calendar-entry-form',
                'preview' => '#calendar_upload_preview',
                'progress' => '#calendar_upload_progress',
                'max' => Yii::$app->getModule('content')->maxAttachedFiles,
            ]) ?>
        </div>
        <div class="col-md-1"></div>
        <div class="col-md-9">
            <?= FilePreview::widget([
                'id' => 'calendar_upload_preview',
                'options' => ['style' => 'margin-top:10px'],
                'model' => $calendarEntryForm->entry,
                'showInStream' => true,
                'edit' => true,
            ]) ?>
        </div>
    </div>
    <br>
    <div id="calendar_upload_progress" style="display:none"></div>
</div>