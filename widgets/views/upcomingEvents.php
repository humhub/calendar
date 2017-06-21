<?php

use yii\helpers\Html;
use humhub\libs\Helpers;

$extraMenus = '<li><a href="'.$calendarUrl.'"><i class="fa fa-arrow-circle-right"></i> '. Yii::t('CalendarModule.widgets_views_nextEvents', 'Open Calendar') .'</a></li>';
?>
<div class="panel calendar-upcoming-snippet" id="calendar-upcoming-events-snippet">

    <div class="panel-heading">
        <i class="fa fa-calendar"></i> <?= Yii::t('CalendarModule.widgets_views_nextEvents', '<strong>Upcoming</strong> events '); ?>
        <?= \humhub\widgets\PanelMenu::widget(['id' => 'calendar-upcoming-events-snippet', 'extraMenus' => $extraMenus]); ?>
    </div>

    <div class="panel-body" style="padding:0px;">
        <ul class="media-list">
            <?php foreach ($calendarEntries as $entry) : ?>
                <?php /* @var $entry \humhub\modules\calendar\models\CalendarEntry */ ?>
                <?php $color = ($entry->color) ? $entry->color : $this->theme->variable('info')?>
                <a href="<?= $entry->getUrl() ?>">
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

</div>

