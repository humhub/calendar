<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\admin\permissions\ManageModules;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\modal\ModalButton;
use yii\helpers\Html;

/* @var $editUrl string */
/* @var $deleteUrl string */
/* @var $title string */
/* @var $isSpaceGlobal */
/* @var $color */
/* @var $disabled */

?>
<div class="media" style="margin-top:5px;">
    <div class="media-body">
        <div class="input-group">
            <span class="input-group-text">
                <i style="display:inline-block;width:16px;height:16px;background-color: <?= Html::encode($color)?>"></i>
            </span>
            <span class="input-group-text flex-fill">
                <?= Html::encode($title) ?>
                <?php if ($disabled) : ?>
                    <?= Badge::warning(Yii::t('CalendarModule.base', 'disabled')) ?>
                <?php endif; ?>
                <?php if ($isSpaceGlobal) : ?>
                    <?= Badge::info(Yii::t('CalendarModule.base', 'global')) ?>
                <?php endif; ?>
            </span>
            <?php if (!$isSpaceGlobal || Yii::$app->user->can([ManageModules::class])) : ?>
            <span class="input-group-text">
                <?= ModalButton::primary()->load($editUrl)->icon('fa-pencil')->sm() ?>
                <?php if (!empty($deleteUrl)) : ?>
                    <?= ModalButton::danger()->post($deleteUrl)->confirm(
                        Yii::t('CalendarModule.config', '<strong>Confirm</strong> Deletion'),
                        Yii::t('CalendarModule.config', 'Do you really want to delte this event type?'),
                        Yii::t('CalendarModule.config', 'Delete'),
                    )->icon('fa-times')->sm()->cssClass('ms-2') ?>
                <?php endif ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>
