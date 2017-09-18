<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
/* @var $this yii\web\View */
/* @var $typeDataProvider \yii\data\ActiveDataProvider */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
/* @var $createUrl string */

use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\widgets\ContainerConfigMenu;
use humhub\modules\calendar\widgets\GlobalConfigMenu;
use humhub\widgets\ActiveForm;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use humhub\widgets\Tabs;
use \yii\helpers\Html;
use yii\widgets\ListView;

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
                <?= Yii::t('CalendarModule.config', 'Event Type Configuration'); ?>
            <?= ModalButton::success(Yii::t('CalendarModule.config', 'Create new type'))->load($createUrl)->icon('fa-plus')->right(); ?>
            </h4>

            <div class="help-block">
                <?= Yii::t('CalendarModule.config', 'Here you can manage your event types.') ?>
            </div>

        </div>
        <br>
        <div>
            <?= ListView::widget([
                'dataProvider' => $typeDataProvider,
                'itemView' => '_calendarEntryTypeItem',
                'viewParams' => [
                        'contentContainer' => $contentContainer
                ],
                'emptyText' => Yii::t('CalendarModule.config', 'There are currently no event types available.')
            ])?>
        </div>

    </div>
</div>
