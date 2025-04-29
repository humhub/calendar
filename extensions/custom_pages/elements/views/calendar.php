<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\extensions\custom_pages\elements\CalendarEntryElement;
use humhub\modules\calendar\widgets\CalendarEntryPicker;
use humhub\modules\ui\form\widgets\ActiveForm;

/* @var CalendarEntryElement $model */
/* @var ActiveForm $form */
?>
<?= $form->field($model, 'id')->widget(CalendarEntryPicker::class, [
    'maxSelection' => 1,
]) ?>
