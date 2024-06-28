<?php

use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\widgets\CalendarControls;
use humhub\modules\calendar\widgets\CalendarFilterBar;
use humhub\modules\calendar\widgets\CalendarTypeLegend;
use humhub\modules\calendar\widgets\ConfigureButton;
use humhub\modules\calendar\widgets\FullCalendar;
use humhub\modules\ui\view\helpers\ThemeHelper;
use humhub\widgets\FooterMenu;

/* @var $this \humhub\modules\ui\view\components\View */
/* @var $selectors array */
/* @var $filters array */
/* @var $editUrl string */

$isFluid = ThemeHelper::isFluid();
$containerClass = $isFluid ? 'container-fluid' : 'container';
$aspectRatio = $isFluid ? 1.9 : 1.5;
?>

<div class="<?= $containerClass ?>">
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong><?= Yii::t('CalendarModule.base', 'Calendar') ?></strong>

            <div class="calendar-option-buttons pull-right">
                <?= CalendarControls::widget([
                    'widgets' => [
                        [ConfigureButton::class, [], ['sortOrder' => 100]],
                    ],
                ]) ?>
            </div>
        </div>
        <div class="panel-body">
            <?= CalendarFilterBar::widget([
                'selectors' => $selectors,
                'filters' => $filters,
                'showControls' => false,
            ]) ?>
        </div>
        <div class="panel-body">
            <?= FullCalendar::widget([
                'canWrite' => !Yii::$app->user->isGuest,
                'aspectRatio' => $aspectRatio,
                'selectors' => $selectors,
                'filters' => $filters,
                'loadUrl' => Url::toAjaxLoad(),
                'editUrl' => $editUrl,
            ]) ?>
        </div>
    </div>

    <?= CalendarTypeLegend::widget() ?>
</div>

<?= FooterMenu::widget(['location' => FooterMenu::LOCATION_FULL_PAGE]) ?>
