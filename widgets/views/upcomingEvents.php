<?php

use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\PanelMenu;
use yii\helpers\Html;
use humhub\libs\Helpers;

/* @var $calendarEntries CalendarEventIF[] */
/* @var $calendarUrl string */

$link = Html::a(Icon::get('arrow-circle-right').' '. Yii::t('CalendarModule.widgets_views_nextEvents', 'Open Calendar'), $calendarUrl);
$extraMenus = Html::tag('li', $link);
?>
<div class="panel calendar-upcoming-snippet" id="calendar-upcoming-events-snippet">

    <div class="panel-heading">
        <?= Icon::get('calendar') ?> <?= Yii::t('CalendarModule.widgets_views_nextEvents', '<strong>Upcoming</strong> events ') ?>
        <?= PanelMenu::widget(['id' => 'calendar-upcoming-events-snippet', 'extraMenus' => $extraMenus]) ?>
    </div>

    <div class="panel-body" style="padding:0;">
        <hr style="margin:0">
        <ul class="media-list">
            <?php foreach ($calendarEntries as $entry) : ?>
                <?php $formatter = new CalendarDateFormatter(['calendarItem' => $entry]); ?>
                <?php $color = ($entry->color) ? $entry->color : $this->theme->variable('info') ?>
                <a href="<?= $entry->getUrl() ?>">
                    <li style="border-left: 3px solid <?= Html::encode($color) ?>">
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

