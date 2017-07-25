<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
use humhub\libs\Html;
use humhub\widgets\ModalButton;

/* @var $model \humhub\modules\calendar\models\CalendarEntryType */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */


$editUrl = $contentContainer->createUrl('/calendar/container-config/edit-type', ['id' => $model->id]);
$deleteUrl = $contentContainer->createUrl('/calendar/container-config/delete-type', ['id' => $model->id]);

?>
<div class="media" style="margin-top:5px;">
    <div class="media-body">
        <div class="input-group">
            <span class="input-group-addon">
                <i style="display:inline-block;width:16px;height:16px;background-color: <?= Html::encode($model->color)?>"></i>
            </span>
            <input class="form-control" value="<?= Html::encode($model->name) ?>" type="text" readonly>
            <span class="input-group-addon">
                <?= ModalButton::primary()->load($editUrl)->icon('fa-pencil')->xs() ?>
                <?= ModalButton::danger()->post($deleteUrl)->confirm(
                        Yii::t('CalendarModule.config', '<strong>Confirm</strong> Deletion'),
                        Yii::t('CalendarModule.config', 'Do you really want to delte this event type?'),
                        Yii::t('CalendarModule.config', 'Delete'))->icon('fa-times')->xs() ?>
            </span>
        </div>
    </div>

</div>