<?php

use yii\helpers\Html;
use humhub\libs\Helpers;
?>
<div class="panel calendar-upcoming-snippet">

    <div class="panel-heading">
        <i class="fa fa-calendar"></i> <?= Yii::t('CalendarModule.widgets_views_nextEvents', '<strong>Upcoming</strong> events '); ?>
    </div>

    <ul class="media-list">
        <?php foreach ($calendarEntries as $entry) : ?>
            <?php /* @var $entry \humhub\modules\calendar\models\CalendarEntry */ ?>
            <?php $color = ($entry->color) ? $entry->color : $this->theme->variable('info')?>
            <a href="<?= $entry->content->container->createUrl('/calendar/entry/view', ['id' => $entry->id]) ?>">
                <li style="border-left: 3px solid <?= $color?>">
                    <div class="media">
                        <div class="media-body  text-break">
                            <?=  humhub\modules\calendar\widgets\EntryBadge::widget(['entry' => $entry]) ?>
                            <strong>
                                <?= Helpers::trimText(Html::encode($entry->title), 60) ?>
                            </strong>
                            
                            <br />
                            <span class="time"><?= humhub\modules\calendar\widgets\EntryDate::widget(['entry' => $entry]); ?></span>
                        </div>
                    </div>
                </li>
            </a>
        <?php endforeach; ?>
    </ul>

</div>

