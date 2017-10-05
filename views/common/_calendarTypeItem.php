<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\helpers\Html;
use humhub\widgets\ModalButton;

/* @var $editUrl string */
/* @var $deleteUrl string */
/* @var $title string */
/* @var $isGlobal */
/* @var $color */
/* @var $disabled */

?>
<div class="media" style="margin-top:5px;">
    <div class="media-body">
        <div class="input-group">
            <span class="input-group-addon">
                <i style="display:inline-block;width:16px;height:16px;background-color: <?= Html::encode($color)?>"></i>
            </span>
            <input class="form-control" value="<?= Html::encode($title) ?><?= ($disabled ? ' - '.Yii::t('CalendarModule.config', '(disabled)') : '') ?>" title="<?= Yii::t('CalendarModule.base', 'Event type color');?>" type="text" readonly>
            <span class="input-group-addon">
                <?php if(empty($editUrl)) : ?>
                    <small><?=  Yii::t('CalendarModule.config', '(global)') ?></small>
                <?php else: ?>
                    <?= ModalButton::primary()->load($editUrl)->icon('fa-pencil')->xs() ?>
                    <?php if(!empty($deleteUrl)) : ?>
                        <?= ModalButton::danger()->post($deleteUrl)->confirm(
                            Yii::t('CalendarModule.config', '<strong>Confirm</strong> Deletion'),
                            Yii::t('CalendarModule.config', 'Do you really want to delte this event type?'),
                            Yii::t('CalendarModule.config', 'Delete'))->icon('fa-times')->xs() ?>
                    <?php endif ?>
                <?php endif; ?>
            </span>
        </div>
    </div>
</div>