<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\widgets\ParticipantList;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;

/* @var $entry CalendarEntry */
?>
<?php ModalDialog::begin(['header' => Yii::t('CalendarModule.views_entry_view', 'Participants of the event "{eventTitle}"', [
    'eventTitle' => $entry->title
])]) ?>
    <div class="modal-body calendar-entry-participants">
        <?= ParticipantList::widget(['entry' => $entry]) ?>
    </div>
    <div class="modal-footer">
        <?= ModalButton::cancel(Yii::t('base', 'Close'))?>
    </div>
<?php ModalDialog::end() ?>