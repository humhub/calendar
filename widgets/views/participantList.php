<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\calendar\widgets\ExportParticipantsButton;
use humhub\modules\calendar\widgets\ParticipantAddForm;
use humhub\modules\calendar\widgets\ParticipantFilter;
use humhub\modules\calendar\widgets\ParticipantItem;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\user\models\User as User;
use humhub\widgets\AjaxLinkPager;
use yii\data\Pagination;
use yii\helpers\Html;

/* @var ActiveForm $form */
/* @var CalendarEntryParticipationForm $model */
/* @var User[] $users */
/* @var Pagination $pagination */
/* @var array $options */
?>
<?php if ($form instanceof ActiveForm) : ?>
    <?= ParticipantAddForm::widget(['form' => $form, 'model' => $model]) ?>

    <?= ParticipantFilter::widget() ?>
<?php endif; ?>

<?= Html::beginTag('div', ['id' => 'calendar-entry-participants-list']) ?>
    <?php if ($pagination->totalCount) : ?>
        <?= ExportParticipantsButton::widget(['entry' => $model->entry]) ?>
    <?php endif; ?>

    <p class="calendar-entry-participants-count"><?= $pagination->totalCount
        ? Yii::t('CalendarModule.views', '{count} Participants', ['count' => '<span>' . $pagination->totalCount . '</span>'])
        : Yii::t('CalendarModule.views', 'No participants')
    ?></p>

    <?= Html::beginTag('ul', ['class' => 'media-list']) ?>
        <?php foreach ($users as $user) : ?>
            <?= ParticipantItem::widget([
                'entry' => $model->entry,
                'user' => $user,
            ])?>
        <?php endforeach; ?>
    <?= Html::endTag('ul') ?>

    <div class="pagination-container">
        <?= AjaxLinkPager::widget([
            'pagination' => $pagination,
            'linkOptions' => ['data' => ['action-click' => 'changeParticipantsListPage']]
        ]); ?>
        <?= Html::hiddenInput('calendar-entry-participants-count', $pagination->totalCount) ?>
    </div>
<?= Html::endTag('div') ?>
