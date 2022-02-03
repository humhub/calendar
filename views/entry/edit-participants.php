<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\calendar\widgets\ParticipantList;
use humhub\modules\ui\form\widgets\ActiveForm;

/* @var $form ActiveForm */
/* @var $calendarEntryParticipationForm CalendarEntryParticipationForm */
?>

<div class="modal-body calendar-entry-participants">
    <?= ParticipantList::widget(['entry' => $calendarEntryParticipationForm->entry]) ?>
</div>

<?php if ($calendarEntryParticipationForm->entry->participation->canAddAll()) : ?>
    <?= $form->field($calendarEntryParticipationForm, 'forceJoin')->checkbox() ?>
<?php endif; ?>