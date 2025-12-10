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
    <?= PanelMenu::widget(['id' => 'calendar-upcoming-events-snippet', 'extraMenus' => $extraMenus]) ?>

    <div class="panel-heading">
        <?= Icon::get('calendar') ?> <?= Yii::t('CalendarModule.views', '<strong>Upcoming</strong> events ') ?>
    </div>

    <div class="panel-body p-0">
        <hr class="m-0">
        <div class="hh-list pb-2">
            <?php foreach ($calendarEntries as $entry) : ?>
                <a href="<?= $entry->getUrl() ?>" class="d-flex" style="border-left: 3px solid <?= Html::encode($entry->color ?: 'var(--info)') ?>">
                    <div class="flex-grow-1 text-break">
                        <?= $entry->getBadge() ?>
                        <strong>
                            <?= Helpers::trimText(Html::encode($entry->getTitle()), 60) ?>
                        </strong>
                        <br>
                        <span class="time"><?= (new CalendarDateFormatter(['calendarItem' => $entry]))->getFormattedTime('medium') ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>
