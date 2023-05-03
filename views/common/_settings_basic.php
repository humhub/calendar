<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\forms\BasicSettings;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\ui\form\widgets\ContentHiddenCheckbox;
use humhub\widgets\Button;

/* @var $basicSettings BasicSettings */
/* @var $form ActiveForm */
?>
<div class="panel-body">
    <h4>
        <?= Yii::t('CalendarModule.config', 'Default basic settings'); ?>
        <?php if ($basicSettings->showResetButton()) : ?>
            <?= Button::defaultType(Yii::t('CalendarModule.config', 'Reset'))
                ->action('client.pjax.post', $basicSettings->getResetButtonUrl())->link()->right()->sm()?>
        <?php endif; ?>
    </h4>

    <div class="help-block">
        <?= $basicSettings->isGlobal()
            ? Yii::t('CalendarModule.config', 'Here you can configure default settings for new calendar events. These settings can be overwritten on space/profile level.')
            : Yii::t('CalendarModule.config', 'Here you can configure default settings for new calendar events.') ?>
    </div>

    <?= $form->field($basicSettings, 'contentHiddenDefault')->widget(ContentHiddenCheckbox::class, [
        'type' => $basicSettings->isGlobal() ? ContentHiddenCheckbox::TYPE_GLOBAL : ContentHiddenCheckbox::TYPE_CONTENTCONTAINER,
    ]) ?>
</div>
