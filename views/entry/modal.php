<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;

/* @var $this \humhub\components\View */
/* @var $entry \humhub\modules\calendar\models\CalendarEntry  */
/* @var $canManageEntries boolean  */
/* @var $editUrl string  */

?>

<?php ModalDialog::begin(['size' => 'large', 'closable' => true]); ?>
    <div class="modal-body" style="padding-bottom:0px">
        <?= $this->renderAjax('view', ['entry' => $entry, 'stream' => false])?>
    </div>
    <div class="modal-footer">
        <?php if($canManageEntries): ?>
            <?= Button::primary(Yii::t('CalendarModule.base', 'Edit'))
                ->action('calendar.editModal', $editUrl, "[data-content-key=".$entry->content->id ."]" )?>
        <?php endif; ?>
        <?= ModalButton::cancel(Yii::t('CalendarModule.base', 'Close')) ?>
    </div>
<?php ModalDialog::end(); ?>
