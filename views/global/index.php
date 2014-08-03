<div class="container">
    <!-- Example row of columns -->
    <div class="row">
        <div class="col-md-10">

            <div class="panel panel-default">
                <div class="panel-body">
                    <?php
                    $this->widget('application.modules.calendar.widgets.FullCalendarWidget', array(
                        'canWrite' => true,
                        'selectors' => $selectors,
                        'filters' => $filters,
                        'loadUrl' => $this->createUrl('loadAjax'),
                        'createUrl' => $this->createUrl('entry/edit', array('uguid' => Yii::app()->user->guid, 'start_time' => '-start-', 'end_time' => '-end-', 'fullCalendar' => '1', 'createFromGlobalCalendar'=>1)),
                    ));
                    ?>

                </div>
            </div>

        </div>
        <div class="col-md-2">
            <div class="panel panel-default">

                <div class="panel-heading">
                    <?php echo Yii::t('CalendarModule.views_global_index', '<strong>Select</strong> calendars'); ?>
                </div>

                <div class="panel-body">

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="selector" class="selectorCheckbox" value="<?php echo CalendarEntry::SELECTOR_MINE; ?>" <?php if (in_array(CalendarEntry::SELECTOR_MINE, $selectors)): ?>checked="checked"<?php endif; ?>>
                            <?php echo Yii::t('CalendarModule.views_global_index', 'My profile'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="selector" class="selectorCheckbox"  value="<?php echo CalendarEntry::SELECTOR_SPACES; ?>" <?php if (in_array(CalendarEntry::SELECTOR_SPACES, $selectors)): ?>checked="checked"<?php endif; ?>>
                            <?php echo Yii::t('CalendarModule.views_global_index', 'My spaces'); ?>
                        </label>
                    </div>
                    <br />

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="selector" class="selectorCheckbox"  value="<?php echo CalendarEntry::SELECTOR_FOLLOWED_SPACES; ?>" <?php if (in_array(CalendarEntry::SELECTOR_FOLLOWED_SPACES, $selectors)): ?>checked="checked"<?php endif; ?>>
                            <?php echo Yii::t('CalendarModule.views_global_index', 'Followed spaces'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="selector" class="selectorCheckbox"  value="<?php echo CalendarEntry::SELECTOR_FOLLOWED_USERS; ?>" <?php if (in_array(CalendarEntry::SELECTOR_FOLLOWED_USERS, $selectors)): ?>checked="checked"<?php endif; ?>>
                            <?php echo Yii::t('CalendarModule.views_global_index', 'Followed users'); ?>
                        </label>
                    </div>

                </div>
            </div>

            <div class="panel panel-default">

                <div class="panel-heading">
                    <?php echo Yii::t('CalendarModule.views_global_index', '<strong>Filter</strong> events'); ?>
                </div>

                <div class="panel-body">

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="filter" class="filterCheckbox" value="<?php echo CalendarEntry::FILTER_PARTICIPATE; ?>" <?php if (in_array(CalendarEntry::FILTER_PARTICIPATE, $filters)): ?>checked="checked"<?php endif; ?>>
                            <?php echo Yii::t('CalendarModule.views_global_index', 'I´m attending'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="filter" class="filterCheckbox" value="<?php echo CalendarEntry::FILTER_MINE; ?>" <?php if (in_array(CalendarEntry::FILTER_MINE, $filters)): ?>checked="checked"<?php endif; ?>>
                            <?php echo Yii::t('CalendarModule.views_global_index', 'My events'); ?>
                        </label>
                    </div>                    
                    <br />
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="filter" class="filterCheckbox"  value="<?php echo CalendarEntry::FILTER_NOT_RESPONDED; ?>" <?php if (in_array(CalendarEntry::FILTER_NOT_RESPONDED, $filters)): ?>checked="checked"<?php endif; ?>>
                            <?php echo Yii::t('CalendarModule.views_global_index', 'Not responded yet'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="filter" class="filterCheckbox"  value="<?php echo CalendarEntry::FILTER_RESPONDED; ?>" <?php if (in_array(CalendarEntry::FILTER_RESPONDED, $filters)): ?>checked="checked"<?php endif; ?>>
                            <?php echo Yii::t('CalendarModule.views_global_index', 'Already responded'); ?>
                        </label>
                    </div>                    
                </div>
            </div>
        </div>        

    </div>
</div>


<script>


</script>    