<?php

use humhub\libs\Helpers;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\modules\ui\icon\widgets\Icon;
use humhub\widgets\PanelMenu;
use yii\helpers\Html;

/* @var $calendarEntries CalendarEventIF[] */
/* @var $calendarUrl string */

$link = Html::a(Icon::get('arrow-circle-right') . ' ' . Yii::t('CalendarModule.views', 'Open Calendar'), $calendarUrl);
$extraMenus = Html::tag('li', $link);
?>
<div class="panel calendar-upcoming-snippet" id="calendar-upcoming-events-snippet">

    <div class="panel-heading">
        <?= Icon::get('calendar') ?> <?= Yii::t('CalendarModule.views', '<strong>Upcoming</strong> events ') ?>
        <?= PanelMenu::widget(['id' => 'calendar-upcoming-events-snippet', 'extraMenus' => $extraMenus]) ?>
    </div>

    <div class="panel-body" style="padding:0">
        <hr style="margin:0">
        <ul class="media-list">
            <?php foreach ($calendarEntries as $entry) : ?>
                <a href="<?= $entry->getUrl() ?>">
                    <li style="border-left: 3px solid <?= Html::encode($entry->color ?: 'var(--info)') ?>">
                        <div class="media">
                            <div class="media-body text-break">
                                <?= $entry->getBadge() ?>
                                <strong>
                                    <?= Helpers::trimText(Html::encode($entry->getTitle()), 60) ?>
                                </strong>
                                <br>
                                <span class="time"><?= (new CalendarDateFormatter(['calendarItem' => $entry]))->getFormattedTime('medium') ?></span>
                            </div>
                        </div>
                    </li>
                </a>
            <?php endforeach; ?>
        </ul>
    </div>

</div>
