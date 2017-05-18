<?php

use yii\helpers\Url;

/* @var $this \humhub\components\View */
?>
<div class="container">
    <div class="panel panel-default">
        <div class="panel-body" style="background-color:<?= $this->theme->variable('background-color-secondary') ?>">
            <?= \humhub\modules\calendar\widgets\CalendarFilterBar::widget([
                    'selectors' => $selectors,
                    'filters' => $filters
            ])?>
        </div>
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-default" style="margin-bottom:0px">
                    <div class="panel-body">
                        <?=
                        \humhub\modules\calendar\widgets\FullCalendar::widget(array(
                            'canWrite' => true,
                            'selectors' => $selectors,
                            'filters' => $filters,
                            'loadUrl' => Url::to(['load-ajax']),
                            'editUrl' => $editUrl,
                        ));
                        ?>

                    </div>
                </div>

            </div>
        </div>

    </div>
</div>