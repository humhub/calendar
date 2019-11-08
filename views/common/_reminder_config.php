<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\forms\ReminderSettings;
use humhub\widgets\Button;

/* @var $reminders \humhub\modules\calendar\models\reminder\CalendarReminder[] */
/* @var $form \humhub\modules\ui\form\widgets\ActiveForm */
?>

<div data-ui-widget="calendar.ReminderForm">

    <?php foreach ($reminders as $index => $reminder): ?>
        <?php if(!$reminder->active) : ?>
            <?php continue; ?>
        <?php endif; ?>

        <div class="row" data-reminder-index="<?= $index ?>">
            <div class="col-md-3">
                <?= $form->field($reminder, "[$index]unit")->dropDownList(ReminderSettings::getUnitSelection())->label(false) ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($reminder, "[$index]value")->textInput(['type' => 'number', 'min' => 1, 'max' => 100])->label(false) ?>
            </div>
            <div class="col-md-7">
                <?= Button::danger()->action('delete')
                    ->icon('fa-times')->xs()->visible(!$reminder->isNewRecord)
                    ->style('margin: 7px 0')->loader(false) ?>

                <?= Button::primary()->action('add')
                    ->icon('fa-plus')->xs()->visible($reminder->isNewRecord)
                    ->style('margin: 7px 0')->loader(false) ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>
