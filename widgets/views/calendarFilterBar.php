<?php

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\widgets\CalendarControls;
use humhub\modules\calendar\widgets\ConfigureButton;
use humhub\modules\calendar\widgets\ExportButton;
use humhub\modules\calendar\widgets\FilterType;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\widgets\bootstrap\Link;
use humhub\widgets\FadeIn;

/* @var $canConfigure bool */
/* @var $canAddEntries bool */
/* @var $selectors array */
/* @var $filters array */
/* @var $showControls bool */
/* @var $showSelectors bool */
/* @var $showFilters bool */
/* @var $showTypes bool */
?>

<?php FadeIn::begin() ?>
    <div id="calendar-overview-loader"></div>
    <?php if ($showControls) : ?>
        <div class="float-end">
            <?= CalendarControls::widget([
                'widgets' => [
                    [ExportButton::class, [], ['sortOrder' => 10]],
                    [ConfigureButton::class, [], ['sortOrder' => 100]],
                ],
            ]) ?>
        </div>
    <?php endif; ?>

    <?= Link::none(Yii::t('CalendarModule.base', 'Filter'))
        ->href('#calendar-filters-container')
        ->icon('filter')
        ->cssClass('filter-toggle-link ')
        ->options(['data-bs-toggle' => 'collapse'])
        ->sm() ?>

    <div id="calendar-filters-container" class="collapse">
        <div class="calendar-options d-md-flex">
            <?php if ($showSelectors) : ?>
                <div>
                    <div class="form-text">
                        <?= Yii::t('CalendarModule.views', 'Select calendars') ?>
                    </div>
                    <div class="d-inline-block">
                        <?php if (Yii::$app->user->identity->moduleManager->isEnabled('calendar')): ?>
                            <div class="checkbox">
                                <label class="calendar_my_profile">
                                    <input type="checkbox" name="selector" class="selectorCheckbox"
                                           value="<?= ActiveQueryContent::USER_RELATED_SCOPE_OWN_PROFILE; ?>"
                                           <?php if (in_array(ActiveQueryContent::USER_RELATED_SCOPE_OWN_PROFILE, $selectors)): ?>checked="checked"<?php endif; ?>>
                                    <?= Yii::t('CalendarModule.views', 'My profile'); ?>
                                </label>
                            </div>
                        <?php endif; ?>
                        <div class="checkbox">
                            <label class="calendar_my_spaces">
                                <input type="checkbox" name="selector" class="selectorCheckbox"
                                       value="<?= ActiveQueryContent::USER_RELATED_SCOPE_SPACES; ?>"
                                       <?php if (in_array(ActiveQueryContent::USER_RELATED_SCOPE_SPACES, $selectors)): ?>checked="checked"<?php endif; ?>>
                                <?= Yii::t('CalendarModule.views', 'My spaces'); ?>
                            </label>
                        </div>
                    </div>

                    <?php if (!Yii::$app->getModule('user')->disableFollow) : ?>
                        <div class="d-inline-block">
                            <div class="checkbox">
                                <label class="calendar_followed_spaces">
                                    <input type="checkbox" name="selector" class="selectorCheckbox"
                                           value="<?= ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_SPACES; ?>"
                                           <?php if (in_array(ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_SPACES, $selectors)): ?>checked="checked"<?php endif; ?>>
                                    <?= Yii::t('CalendarModule.views', 'Followed spaces'); ?>
                                </label>
                            </div>
                            <div class="checkbox">
                                <label class="calendar_followed_users">
                                    <input type="checkbox" name="selector" class="selectorCheckbox"
                                           value="<?= ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS; ?>"
                                           <?php if (in_array(ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS, $selectors)): ?>checked="checked"<?php endif; ?>>
                                    <?= Yii::t('CalendarModule.views', 'Followed users'); ?>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif ?>
            <?php if ($showFilters) : ?>
                <div>
                    <div class="form-text">
                        <?= Yii::t('CalendarModule.views', 'Filter events') ?>
                    </div>
                    <div class="d-inline-block">
                        <div class="checkbox">
                            <label class="calendar_filter_participate">
                                <input type="checkbox" name="filter" class="filterCheckbox"
                                       value="<?= CalendarEntry::FILTER_PARTICIPATE; ?>"
                                       <?php if (in_array(CalendarEntry::FILTER_PARTICIPATE, $filters)): ?>checked="checked"<?php endif; ?>>
                                <?= Yii::t('CalendarModule.views', 'I\'m attending'); ?>
                            </label>
                        </div>
                        <div class="checkbox">
                            <label class="calendar_filter_mine">
                                <input type="checkbox" name="filter" class="filterCheckbox"
                                       value="<?= CalendarEntry::FILTER_MINE; ?>"
                                       <?php if (in_array(CalendarEntry::FILTER_MINE, $filters)): ?>checked="checked"<?php endif; ?>>
                                <?= Yii::t('CalendarModule.views', 'My events'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            <?php endif ?>
            <?php if ($showTypes) : ?>
                <div class="flex-grow-1">
                    <div class="form-text">
                        <?= Yii::t('CalendarModule.base', 'Filter by types') ?>
                    </div>
                    <div style="max-width:300px">
                        <?= FilterType::widget(['name' => 'filterType']) ?>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
<?php FadeIn::end() ?>
