<?php

use humhub\helpers\Html;
use humhub\modules\calendar\widgets\CalendarFilterBar;
use humhub\modules\calendar\widgets\FilterType;
use humhub\widgets\bootstrap\Button;

/* @var $viewMode string current CalendarFilterBar::VIEW_* value */
/* @var $calendars string current CalendarFilterBar::CALENDARS_* value */
/* @var $show string current CalendarFilterBar::SHOW_* value */
/* @var $viewOptions array */
/* @var $calendarsOptions array */
/* @var $showOptions array */
/* @var $showSelectors bool */
/* @var $showFilters bool */
/* @var $showTypes bool */
/* @var $typeSelection array selected CalendarEntryType models */
/* @var $isFiltered bool */
?>
<div class="container-cards calendar-filter-bar">
    <div id="calendar-overview-loader"></div>
    <div class="form-search">
        <div class="d-flex flex-wrap gap-2">
            <?php if ($showSelectors) : ?>
                <div class="flex-fill calendar-filter-view-mode">
                    <div class="form-search-field-info"><?= Yii::t('CalendarModule.views', 'View') ?></div>
                    <?= Html::dropDownList('viewMode', $viewMode, $viewOptions, [
                        'class' => 'form-control calendar-select-view-mode',
                    ]) ?>
                </div>
                <div class="flex-fill calendar-filter-calendars<?= $viewMode === CalendarFilterBar::VIEW_NETWORK ? ' d-none' : '' ?>">
                    <div class="form-search-field-info"><?= Yii::t('CalendarModule.views', 'Calendars') ?></div>
                    <?= Html::dropDownList('calendars', $calendars, $calendarsOptions, [
                        'class' => 'form-control calendar-select-calendars',
                    ]) ?>
                </div>
            <?php endif ?>

            <?php if ($showFilters) : ?>
                <div class="flex-fill calendar-filter-show">
                    <div class="form-search-field-info"><?= Yii::t('CalendarModule.views', 'Show only') ?></div>
                    <?= Html::dropDownList('show', $show, $showOptions, [
                        'class' => 'form-control calendar-select-show',
                    ]) ?>
                </div>
            <?php endif ?>

            <?php if ($showTypes) : ?>
                <div class="flex-fill calendar-filter-types">
                    <div class="form-search-field-info"><?= Yii::t('CalendarModule.base', 'Event types') ?></div>
                    <?= FilterType::widget(['name' => 'filterType', 'selection' => $typeSelection]) ?>
                </div>
            <?php endif ?>

            <div class="form-search-action form-search-action-reset<?= $isFiltered ? '' : ' d-none' ?>">
                <?= Button::danger()
                    ->icon('times')
                    ->cssClass('calendar-filter-reset')
                    ->tooltip(Yii::t('CalendarModule.views', 'Reset filters'))
                    ->loader(false) ?>
            </div>
        </div>
    </div>
</div>
