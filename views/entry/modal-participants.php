<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\assets\ParticipationFormAssets;
use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
use humhub\widgets\Tabs;
use yii\helpers\Html;
use yii\web\View;

/* @var $calendarEntryParticipationForm CalendarEntryParticipationForm */
/* @var $activeTab string|null */
/* @var $widgetOptions array */
/* @var $saveUrl string */
/* @var $this View  */

ParticipationFormAssets::register($this);
?>
<?php ModalDialog::begin(['header' =>'<strong>' . Yii::t('CalendarModule.views_entry_view', 'Event Participants') . '</strong>']) ?>
    <?php $form = ActiveForm::begin(['enableClientValidation' => false]) ?>
        <?= Html::beginTag('div', $widgetOptions) ?>
            <strong id="calendar-entry-participation-settings-title"<?php if ($calendarEntryParticipationForm->entry->participation->isEnabled()) : ?> style="display:none"<?php endif;?>>
                <?= Yii::t('CalendarModule.views_entry_edit', 'Settings') ?>
            </strong>
            <?= Tabs::widget([
                'viewPath' => '@calendar/views/entry',
                'params' => ['form' => $form, 'calendarEntryParticipationForm' => $calendarEntryParticipationForm],
                'options' => [
                    'id' => 'calendar-entry-participation-tabs',
                    'style' => !$calendarEntryParticipationForm->entry->participation->isEnabled() ? 'display:none' : false,
                ],
                'items' => [
                    [
                        'label' => Yii::t('CalendarModule.views_entry_edit', 'Settings'),
                        'view' => 'edit-participation',
                        'linkOptions' => ['class' => 'tab-participation'],
                        'active' => (empty($activeTab) || $activeTab === 'settings'),
                    ],
                    [
                        'label' => Yii::t('CalendarModule.views_entry_edit', 'Participants of the event'),
                        'view' => 'edit-participants',
                        'linkOptions' => ['class' => 'tab-participants'],
                        'active' => ($activeTab === 'list'),
                    ],
                ]
            ]) ?>

            <hr>

            <div class="modal-footer">
                <?= ModalButton::submitModal($saveUrl) ?>
                <?= ModalButton::cancel() ?>
            </div>
        <?= Html::endTag('div') ?>
    <?php ActiveForm::end() ?>
<?php ModalDialog::end() ?>