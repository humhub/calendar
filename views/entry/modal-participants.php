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
/* @var $editUrl string */
/* @var $saveUrl string */
/* @var $isNewRecord bool */
/* @var $buttons array */
/* @var $this View  */

ParticipationFormAssets::register($this);

$isParticipationEnabled = $calendarEntryParticipationForm->entry->participation->isEnabled();
$hiddenStyle = ['style' => 'display:none'];
$visibleStyle = [];
?>
<?php ModalDialog::begin([
    'header' =>'<strong>' . Yii::t('CalendarModule.views', 'Participants') . '</strong>',
    'size' => 'large',
]) ?>
    <?php $form = ActiveForm::begin(['enableClientValidation' => false]) ?>
        <?= Html::beginTag('div', $widgetOptions) ?>
            <?= Tabs::widget([
                'viewPath' => '@calendar/views/entry',
                'params' => ['form' => $form, 'calendarEntryParticipationForm' => $calendarEntryParticipationForm, 'renderWrapper' => true],
                'options' => [
                    'id' => 'calendar-entry-participation-tabs',
                ],
                'items' => [
                    [
                        'label' => Yii::t('CalendarModule.views', 'Settings'),
                        'view' => 'edit-participation',
                        'linkOptions' => ['class' => 'tab-participation'],
                        'active' => (empty($activeTab) || $activeTab === 'settings'),
                        'visible' => $calendarEntryParticipationForm->entry->content->canEdit()
                    ],
                    [
                        'label' => Yii::t('CalendarModule.views', 'Participants'),
                        'view' => 'edit-participants',
                        'linkOptions' => ['class' => 'tab-participants'],
                        'headerOptions' => $isParticipationEnabled ? $visibleStyle : $hiddenStyle,
                        'active' => ($activeTab === 'list'),
                    ],
                ]
            ]) ?>

            <hr>

            <div class="modal-footer">
                <?php if ($isNewRecord) : ?>
                    <?= ModalButton::defaultType(Yii::t('CalendarModule.views', 'Back'))
                            ->action('back', $editUrl)
                            ->id('calendar-entry-participation-button-back')
                            ->loader(false) ?>
                    <?= ModalButton::primary(Yii::t('CalendarModule.views', 'Next'))
                            ->action('next')
                            ->id('calendar-entry-participation-button-next')
                            ->options($isParticipationEnabled ? $visibleStyle : $hiddenStyle)
                            ->loader(false) ?>
                <?php else : ?>
                    <?= ModalButton::cancel(Yii::t('CalendarModule.views', 'Close'))
                            ->id('calendar-entry-participation-button-close') ?>
                <?php endif; ?>
                <?php if ($calendarEntryParticipationForm->entry->content->canEdit()) : ?>
                    <?= ModalButton::submitModal($saveUrl)
                        ->id('calendar-entry-participation-button-save')
                        ->options(!$isNewRecord || !$isParticipationEnabled ? $visibleStyle : $hiddenStyle) ?>
                <?php endif; ?>
            </div>
        <?= Html::endTag('div') ?>
    <?php ActiveForm::end() ?>
<?php ModalDialog::end() ?>