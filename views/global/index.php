<?php

use humhub\modules\calendar\widgets\CalendarFilterBar;
use humhub\modules\calendar\widgets\FullCalendar;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\ui\view\helpers\ThemeHelper;

/* @var $this \humhub\components\View */
/* @var $selectors array */
/* @var $filters array */

$isFluid = ThemeHelper::isFluid();
$containerClass = $isFluid ? 'container-fluid' : 'container';
$aspectRatio = $isFluid ? 1.9 : 1.5;

?>
<div class="<?= $containerClass ?>">
    <div class="panel panel-default">
        <div class="panel-body" style="background-color:<?= $this->theme->variable('background-color-secondary'); ?>border-radius:4px;">
            <?= CalendarFilterBar::widget([
                    'selectors' => $selectors,
                    'filters' => $filters
            ])?>
        </div>
        <div class="panel panel-default" style="margin-bottom:0px">
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

    </div>
</div>