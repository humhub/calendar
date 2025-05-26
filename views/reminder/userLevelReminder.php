<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\helpers\Html;
use humhub\modules\calendar\models\reminder\forms\ReminderSettings;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/* @var ReminderSettings $reminderSettings */
?>
<?php $form = Modal::beginFormDialog([
    'header' => Yii::t('CalendarModule.base', '<strong>Reminder</strong> settings'),
    'footer' => ModalButton::cancel() . ModalButton::save(),
]) ?>
    <div class="help-block">
        <?= Yii::t('CalendarModule.base', 'Your reminder settings for event: <strong>\'{title}\'</strong>', [
            'title' => Html::encode($reminderSettings->entry->getTitle())
        ])?>
    </div>
    <hr>
    <?= $this->render('@calendar/views/common/_reminder_config', ['settings' => $reminderSettings, 'form' => $form])?>
<?php Modal::endFormDialog() ?>
