<?php
use yii\helpers\Url;
?>
<button data-action-click="calendar.editModal"  data-ui-loader
        data-action-url="<?= $editUrl ?>"
        data-action-target="[data-content-key='<?= $entry->content->id ?>']"
        class="btn btn-primary">
    <?= Yii::t('CalendarModule.base', 'Edit'); ?>
</button>
<button data-modal-close class="btn btn-default"><?= Yii::t('CalendarModule.base', 'Close'); ?></button>