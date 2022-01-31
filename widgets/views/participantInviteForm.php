<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\user\widgets\UserPickerField;
use humhub\widgets\Button;
use yii\helpers\Html;

/* @var CalendarEntry $entry */
?>
<?= Html::beginTag('li', ['class' => 'calendar-entry-new-participants-form']) ?>
    <div class="media">
        <div class="media-body">
            <?= UserPickerField::widget([
                'name' => 'newParticipants',
                'placeholder' => Yii::t('AdminModule.user', 'Invite new participants...'),
                'options' => ['label' => false],
            ]) ?>
        </div>
        <div class="media-body">
            <?= Button::info()->xs()
                ->icon('send')
                ->action('add', $entry->content->container->createUrl('/calendar/entry/invite-participants')) ?>
        </div>
    </div>
<?= Html::endTag('li') ?>