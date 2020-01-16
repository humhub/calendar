<?php

use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\widgets\PanelMenu;
use yii\helpers\Html;
use humhub\libs\Helpers;

/* @var $calendarEntries CalendarEventIF[] */
/* @var $calendarUrl string */

$extraMenus = '<li><a href="'.$calendarUrl.'"><i class="fa fa-arrow-circle-right"></i> '. Yii::t('CalendarModule.widgets_views_nextEvents', 'Open Calendar') .'</a></li>';
?>
<div class="panel calendar-upcoming-snippet" id="calendar-upcoming-events-snippet">

    <div class="panel-heading">
        <i class="fa fa-calendar"></i> <?= Yii::t('CalendarModule.widgets_views_nextEvents', '<strong>Upcoming</strong> events '); ?>
        <?= PanelMenu::widget(['id' => 'calendar-upcoming-events-snippet', 'extraMenus' => $extraMenus]); ?>
    </div>

    <div class="panel-body" style="padding:0px;">
        <hr style="margin:0px">
        <ul class="media-list">
            <?php foreach ($calendarEntries as $entry) : ?>
                <?php $formatter = new CalendarDateFormatter(['calendarItem' => $entry]); ?>
                <?php $color = ($entry->color) ? $entry->color : $this->theme->variable('info')?>
                <a href="<?= $entry->getUrl() ?>">
                    <li style="border-left: 3px solid <?= $color?>">
                        <div class="media">
                            <div class="media-body  text-break">
                                <?=  $entry->getBadge() ?>
                                <strong>
                                    <?= Helpers::trimText(Html::encode($entry->getTitle()), 60) ?>
                                </strong>

                                <br />
                                <span class="time"><?= $formatter->getFormattedTime('medium') ?></span>
                            </div>
                        </div>
                    </li>
                </a>
            <?php endforeach; ?>
        </ul>
    </div>

</div>

