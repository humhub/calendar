<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
use humhub\widgets\Button;
use humhub\widgets\ModalButton;

$deleteUrl = $contentContainer->createUrl('/calendar/entry/delete', ['id' => $entry->id, 'cal' => 1]);
?>

<?php \humhub\widgets\ModalDialog::begin(['size' => 'large']); ?>
    <div class="modal-body" style="padding-bottom:0px">
        <?= $content ?>
    </div>
    <div class="modal-footer">
        <?php if($canManageEntries): ?>
            <?= Button::primary(Yii::t('CalendarModule.base', 'Edit'))
                ->action('calendar.editModal', $editUrl, "[data-content-key=".$entry->content->id ."]" )?>
        <?php endif; ?>
        <?= ModalButton::cancel(Yii::t('CalendarModule.base', 'Close')) ?>
    </div>
<?php \humhub\widgets\ModalDialog::end(); ?>
