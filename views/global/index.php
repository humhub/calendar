<?php

use humhub\helpers\ThemeHelper;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\widgets\CalendarControls;
use humhub\modules\calendar\widgets\CalendarFilterBar;
use humhub\modules\calendar\widgets\CalendarTypeLegend;
use humhub\modules\calendar\widgets\ConfigureButton;
use humhub\modules\calendar\widgets\ExportButton;
use humhub\modules\calendar\widgets\FullCalendar;
use humhub\widgets\FooterMenu;

/* @var $view string */
/* @var $calendars string */
/* @var $show string */
/* @var $types array */
/* @var $editUrl string */

$isFluid = ThemeHelper::isFluid();
$containerClass = $isFluid ? 'container-fluid' : 'container';
$aspectRatio = $isFluid ? 1.9 : 1.5;
?>

<div class="<?= $containerClass ?>">
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong><?= Yii::t('CalendarModule.base', 'Calendar') ?></strong>

            <div class="float-end">
                <?= CalendarControls::widget([
                    'widgets' => [
                        [ExportButton::class, ['global' => true], ['sortOrder' => 10]],
                        [ConfigureButton::class, [], ['sortOrder' => 100]],
                    ],
                ]) ?>
            </div>
        </div>
        <?php if (!Yii::$app->user->isGuest) : ?>
        <div class="panel-body">
            <?= CalendarFilterBar::widget([
                'view' => $view,
                'calendars' => $calendars,
                'show' => $show,
                'types' => $types,
            ]) ?>
        </div>
        <?php endif ?>
        <div class="panel-body">
            <?= FullCalendar::widget([
                'canWrite' => !Yii::$app->user->isGuest,
                'aspectRatio' => $aspectRatio,
                'view' => $view,
                'calendars' => $calendars,
                'show' => $show,
                'types' => $types,
                'loadUrl' => Url::toAjaxLoad(),
                'editUrl' => $editUrl,
            ]) ?>
        </div>
    </div>

    <?= CalendarTypeLegend::widget() ?>
</div>

<?= FooterMenu::widget(['location' => FooterMenu::LOCATION_FULL_PAGE]) ?>
