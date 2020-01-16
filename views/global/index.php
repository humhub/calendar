<?php

use humhub\modules\calendar\widgets\CalendarFilterBar;
use humhub\modules\calendar\widgets\FullCalendar;
use humhub\modules\calendar\helpers\Url;

/* @var $this \humhub\components\View */
/* @var $selectors array */
/* @var $filters array */
?>
<div class="container">
    <div class="panel panel-default">
        <div class="panel-body" style="background-color:<?= $this->theme->variable('background-color-secondary'); ?>border-radius:4px;">
            <?= CalendarFilterBar::widget([
                    'selectors' => $selectors,
                    'filters' => $filters
            ])?>
        </div>
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-default" style="margin-bottom:0px">
                    <div class="panel-body">
                        <?= FullCalendar::widget([
                            'canWrite' => !Yii::$app->user->isGuest,
                            'selectors' => $selectors,
                            'filters' => $filters,
                            'loadUrl' => Url::toAjaxLoad(),
                            'editUrl' => $editUrl,
                        ]); ?>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>