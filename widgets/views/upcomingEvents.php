<?php

use humhub\libs\Helpers;
use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\widgets\Icon;
use humhub\widgets\menu\MenuLink;
use humhub\widgets\SidebarCard;
use yii\helpers\Html;

/* @var $calendarEntries CalendarEventIF[] */
/* @var $calendarUrl string */
?>
<?php SidebarCard::begin([
    'title' => Icon::get('calendar') . ' ' . Yii::t('CalendarModule.views', '<strong>Upcoming</strong> events '),
    'encodeTitle' => false,
    'menu' => [new MenuLink(['label' => Yii::t('CalendarModule.views', 'Open Calendar'), 'url' => $calendarUrl, 'icon' => 'circle-arrow-right-filled'])],
    'options' => ['id' => 'calendar-upcoming-events-snippet', 'class' => 'calendar-upcoming-snippet'],
    'bodyOptions' => ['class' => 'p-0'],
]) ?>
        <hr class="m-0">
        <div class="hh-list pb-2">
            <?php foreach ($calendarEntries as $entry) : ?>
                <a href="<?= $entry->getUrl() ?>" class="d-flex" style="border-left: 3px solid <?= Html::encode($entry->color ?: 'var(--bs-info)') ?>">
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
<?php SidebarCard::end() ?>
