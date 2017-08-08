<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
use humhub\libs\Html;
use humhub\widgets\ModalButton;
use yii\helpers\Url;

/* @var $model \humhub\modules\calendar\models\CalendarEntryType */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */


if($contentContainer) {
    $editUrl = $contentContainer->createUrl('/calendar/container-config/edit-type', ['id' => $model->id]);
    $deleteUrl = $contentContainer->createUrl('/calendar/container-config/delete-type', ['id' => $model->id]);
} else {
    $editUrl = URL::to(['/calendar/config/edit-type', 'id' => $model->id]);
    $deleteUrl = URL::to(['/calendar/config/delete-type', 'id' => $model->id]);
}

?>
<div class="media" style="margin-top:5px;">
    <div class="media-body">
        <div class="input-group">
            <span class="input-group-addon">
                <i style="display:inline-block;width:16px;height:16px;background-color: <?= Html::encode($model->color)?>"></i>
            </span>
            <input class="form-control" value="<?= Html::encode($model->name) ?>" title="<?= Yii::t('CalendarModule.base', 'Event type color');?>" type="text" readonly>
            <span class="input-group-addon">
                <?php if($contentContainer && $model->contentcontainer_id == null) : ?>
                    <small><?=  Yii::t('CalendarModule.config', '(global)') ?></small>
                <?php else: ?>
                    <?= ModalButton::primary()->load($editUrl)->icon('fa-pencil')->xs() ?>
                    <?= ModalButton::danger()->post($deleteUrl)->confirm(
                        Yii::t('CalendarModule.config', '<strong>Confirm</strong> Deletion'),
                        Yii::t('CalendarModule.config', 'Do you really want to delte this event type?'),
                        Yii::t('CalendarModule.config', 'Delete'))->icon('fa-times')->xs() ?>
                <?php endif; ?>
            </span>
        </div>
    </div>

</div>