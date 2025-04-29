<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\extensions\custom_pages\elements\CalendarEventsElement;
use humhub\modules\calendar\widgets\CalendarEntryPicker;
use humhub\modules\ui\form\widgets\ActiveForm;

/* @var CalendarEventsElement $model */
/* @var ActiveForm $form */
?>
<div class="records-content-form-fields" data-type="static">
    <?= $form->field($model, 'static')->widget(CalendarEntryPicker::class) ?>
</div>

<div class="records-content-form-fields" data-type="options">
    <?= $form->field($model, 'nextDays') ?>
    <?= $form->field($model, 'sortOrder')->radioList([
        $model::SORT_DATE_OLD => Yii::t('CalendarModule.base', 'From Oldest to Newest'),
        $model::SORT_DATE_NEW => Yii::t('CalendarModule.base', 'From Newest to Oldest'),
    ]) ?>
</div>
