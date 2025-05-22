<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\assets\ParticipationFormAssets;
use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\widgets\bootstrap\Tabs;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;
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

$formButtons = $isNewRecord
    ? ModalButton::light(Yii::t('CalendarModule.views', 'Back'))
        ->action('back', $editUrl)
        ->id('calendar-entry-participation-button-back')
        ->loader(false)
    . ModalButton::primary(Yii::t('CalendarModule.views', 'Next'))
        ->action('next')
        ->id('calendar-entry-participation-button-next')
        ->options($isParticipationEnabled ? $visibleStyle : $hiddenStyle)
        ->loader(false)
    : ModalButton::cancel(Yii::t('CalendarModule.views', 'Close'))
        ->id('calendar-entry-participation-button-close');
if ($calendarEntryParticipationForm->entry->content->canEdit()) {
    $formButtons .= ModalButton::save(null, $saveUrl)
        ->id('calendar-entry-participation-button-save')
        ->options(!$isNewRecord || !$isParticipationEnabled ? $visibleStyle : $hiddenStyle);
}
?>
<?php $form = Modal::beginFormDialog([
    'header' => '<strong>' . Yii::t('CalendarModule.views', 'Participants') . '</strong>',
    'size' => 'large',
    'form' => ['enableClientValidation' => false],
    'footer' => $formButtons,
]) ?>
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
                    'visible' => $calendarEntryParticipationForm->entry->content->canEdit(),
                ],
                [
                    'label' => Yii::t('CalendarModule.views', 'Participants'),
                    'view' => 'edit-participants',
                    'linkOptions' => ['class' => 'tab-participants'],
                    'headerOptions' => $isParticipationEnabled ? $visibleStyle : $hiddenStyle,
                    'active' => ($activeTab === 'list'),
                ],
            ],
        ]) ?>
    <?= Html::endTag('div') ?>
<?php Modal::endFormDialog() ?>
