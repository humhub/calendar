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
                    <?= Html::label(Yii::t('CalendarModule.views', 'View'), 'calendar-select-view-mode', ['class' => 'form-search-field-info']) ?>
                    <?= Html::dropDownList('viewMode', $viewMode, $viewOptions, [
                        'id' => 'calendar-select-view-mode',
                        'class' => 'form-control calendar-select-view-mode',
                    ]) ?>
                </div>
                <div class="flex-fill calendar-filter-calendars<?= $viewMode === CalendarFilterBar::VIEW_NETWORK ? ' d-none' : '' ?>">
                    <?= Html::label(Yii::t('CalendarModule.views', 'Calendars'), 'calendar-select-calendars', ['class' => 'form-search-field-info']) ?>
                    <?= Html::dropDownList('calendars', $calendars, $calendarsOptions, [
                        'id' => 'calendar-select-calendars',
                        'class' => 'form-control calendar-select-calendars',
                    ]) ?>
                </div>
            <?php endif ?>

            <?php if ($showFilters) : ?>
                <div class="flex-fill calendar-filter-show">
                    <?= Html::label(Yii::t('CalendarModule.views', 'Show only'), 'calendar-select-show', ['class' => 'form-search-field-info']) ?>
                    <?= Html::dropDownList('show', $show, $showOptions, [
                        'id' => 'calendar-select-show',
                        'class' => 'form-control calendar-select-show',
                    ]) ?>
                </div>
            <?php endif ?>

            <?php if ($showTypes) : ?>
                <div class="flex-fill calendar-filter-types">
                    <?= Html::label(Yii::t('CalendarModule.base', 'Event types'), 'calendar-filter-type', ['class' => 'form-search-field-info']) ?>
                    <?= FilterType::widget(['id' => 'calendar-filter-type', 'name' => 'filterType', 'selection' => $typeSelection]) ?>
                </div>
            <?php endif ?>

            <div class="form-search-action form-search-action-reset<?= $isFiltered ? '' : ' d-none' ?>">
                <?= Button::danger()
                    ->icon('x')
                    ->cssClass('calendar-filter-reset')
                    ->tooltip(Yii::t('CalendarModule.views', 'Reset filters'))
                    ->loader(false) ?>
            </div>
        </div>
    </div>
</div>
