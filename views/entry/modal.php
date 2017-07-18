<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
?>

<?php \humhub\widgets\ModalDialog::begin(['size' => 'large']); ?>
    <div class="modal-body" style="padding-bottom:0px">
        <?= $content ?>
    </div>
    <div class="modal-footer">
        <div class="row">
            <div class="col-md-3"></div>
            <div class="col-md-6" style="text-align:center">
                <?php if($canManageEntries): ?>
                    <button data-action-click="calendar.editModal"  data-ui-loader
                            data-action-url="<?= $editUrl ?>"
                            data-action-target="[data-content-key='<?= $entry->content->id ?>']"
                            class="btn btn-primary">
                        <?= Yii::t('CalendarModule.base', 'Edit'); ?>
                    </button>
                <?php endif; ?>
                <button data-modal-close class="btn btn-default"><?= Yii::t('CalendarModule.base', 'Close'); ?></button>
            </div>
            <div class="col-md-3" >
                <?php if($canManageEntries): ?>
                <button class="btn btn-danger pull-right" data-ui-loader
                        data-action-click="calendar.deleteEvent" data-action-url="<?= $contentContainer->createUrl('/calendar/entry/delete', ['id' => $entry->id, 'cal' => 1]) ?>"
                        data-action-confirm="<?= Yii::t('CalendarModule.base', 'Are you sure you want to delete this event?')?>">
                    <?= Yii::t('CalendarModule.views_entry_edit', 'Delete') ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php \humhub\widgets\ModalDialog::end(); ?>
