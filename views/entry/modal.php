<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\ui\view\components\View;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\modal\Modal;

/* @var $this View */
/* @var $entry CalendarEntry */
/* @var $editUrl string  */
/* @var $inviteUrl string  */
?>
<?php Modal::beginDialog([
    'size' => 'large',
    'closable' => true,
    'showClose' => true,
    'footer' => ($editUrl ? Button::primary(Yii::t('CalendarModule.base', 'Edit'))
            ->action('calendar.editModal', $editUrl, "[data-content-key=".$entry->content->id ."]" ) : '') .
        ($inviteUrl ? Button::info(Yii::t('CalendarModule.base', 'Invite'))
            ->action('ui.modal.load', $inviteUrl, "[data-content-key=".$entry->content->id ."]" ) : ''),
]) ?>
    <?= $this->renderAjax('view', ['entry' => $entry, 'stream' => false]) ?>
<?php Modal::endDialog() ?>
