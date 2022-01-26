<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\widgets\ParticipantList;

/* @var $calendarEntryForm CalendarEntryForm */
?>

<div class="modal-body">
    <?= ParticipantList::widget(['entry' => $calendarEntryForm->entry]) ?>
</div>