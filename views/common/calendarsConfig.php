<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\calendar\widgets\ContainerConfigMenu;
use humhub\modules\calendar\widgets\GlobalConfigMenu;

/* @var $this \humhub\components\View */
/* @var $calendars \humhub\modules\calendar\interfaces\CalendarItemType[] */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
?>



<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('CalendarModule.config', '<strong>Calendar</strong> module configuration'); ?></div>

<?php if($contentContainer === null) : ?>
    <?= GlobalConfigMenu::widget() ?>
<?php else: ?>
    <?= ContainerConfigMenu::widget()?>
<?php endif; ?>

<div class="panel-body">
    <div class="clearfix">
        <h4>
            <?= Yii::t('CalendarModule.config', 'Other Calendars Configuration'); ?>
        </h4>

        <div class="help-block">
            <?= Yii::t('CalendarModule.config', 'Here you can manage calendar types provided by other modules.') ?>
        </div>

    </div>

    <br>

    <div>
        <?php foreach($calendars as $itemType) : ?>
            <?= $this->render('_calendarTypeItem', [
                'editUrl' => $itemType->getEditUrl(),
                'color' => $itemType->getColor($contentContainer),
                'deleteUrl' => null,
                'title' => $itemType->getTitle(),
                'disabled' => !$itemType->enabled,
            ]);?>
        <?php endforeach; ?>
    </div>

</div>
</div>