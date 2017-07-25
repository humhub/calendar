<?php

use humhub\modules\calendar\widgets\CalendarFilterBar;
use humhub\modules\calendar\widgets\FullCalendar;
use yii\helpers\Url;

/* @var $this \humhub\components\View */
/* @var $canConfigure boolean */
/* @var $configureUrl string */
?>
<div class="container">
    <div class="panel panel-default">
        <div class="panel-body" style="background-color:<?= $this->theme->variable('background-color-secondary') ?>">
            <?= CalendarFilterBar::widget([
                    'selectors' => $selectors,
                    'canConfigure' => $canConfigure,
                    'configUrl' => $configureUrl,
                    'filters' => $filters
            ])?>
        </div>
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-default" style="margin-bottom:0px">
                    <div class="panel-body">
                        <?=
                        FullCalendar::widget([
                            'canWrite' => true,
                            'selectors' => $selectors,
                            'filters' => $filters,
                            'loadUrl' => Url::to(['load-ajax']),
                            'editUrl' => $editUrl,
                        ]);
                        ?>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>