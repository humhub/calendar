<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\calendar\interfaces\event\CalendarTypeSetting;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/* @var $model CalendarEntryType|CalendarTypeSetting */

if ($model instanceof CalendarTypeSetting) {
    $title = Yii::t('CalendarModule.views', '<strong>Edit</strong> calendar');
    $titleAttribute = 'title';
    $titleDisabled = true;
} else {
    $title = ($model->isNewRecord)
        ? Yii::t('CalendarModule.views', '<strong>Create</strong> new event type')
        : Yii::t('CalendarModule.views', '<strong>Edit</strong> event type');
    $titleAttribute = 'name';
    $titleDisabled = false;
}

?>

<?php $form = Modal::beginFormDialog([
    'title' => Yii::t('CalendarModule.views', $title),
    'footer' => ModalButton::save() . ModalButton::cancel(),
]) ?>
    <div id="event-type-color-field" class="input-group mb-3 input-color-group">
        <div class="input-group-prepend">
            <?= $form->field($model, 'color')
                ->colorInput(['style' => 'border-radius: var(--bs-border-radius) 0 0 var(--bs-border-radius)'])
                ->label(false) ?>
        </div>
        <?= $form->field($model, $titleAttribute, ['options' => ['class' => 'flex-grow-1']])
            ->textInput([
                'disabled' => $titleDisabled,
                'placeholder' => Yii::t('CalendarModule.config', 'Name'),
                'maxlength' => 100,
                'autofocus' => '',
                'style' => 'margin-left: -1px; border-radius: 0 var(--bs-border-radius) var(--bs-border-radius) 0',
            ])->label(false) ?>
    </div>
    <?php if ($model instanceof CalendarTypeSetting && $model->canBeDisabled()) : ?>
        <?= $form->field($model, 'enabled')->checkbox() ?>
    <?php endif; ?>
<?php Modal::endFormDialog() ?>
