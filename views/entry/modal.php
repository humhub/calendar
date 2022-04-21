<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\widgets\Button;
use humhub\widgets\ModalDialog;

/* @var $this \humhub\modules\ui\view\components\View */
/* @var $entry \humhub\modules\calendar\models\CalendarEntry */
/* @var $editUrl string  */
/* @var $inviteUrl string  */
?>
<?php ModalDialog::begin(['size' => 'large', 'closable' => true, 'showClose' => true]); ?>
    <div class="modal-body" style="padding-bottom:0px">
        <?= $this->renderAjax('view', ['entry' => $entry, 'stream' => false])?>
    </div>
    <div class="modal-footer">
        <?php if ($editUrl) : ?>
            <?= Button::primary(Yii::t('CalendarModule.base', 'Edit'))
                ->action('calendar.editModal', $editUrl, "[data-content-key=".$entry->content->id ."]" )?>
        <?php endif; ?>
        <?php if ($inviteUrl) : ?>
            <?= Button::primary(Yii::t('CalendarModule.base', 'Invite'))
                ->action('ui.modal.load', $inviteUrl, "[data-content-key=".$entry->content->id ."]" )?>
        <?php endif; ?>
    </div>
<?php ModalDialog::end(); ?>
