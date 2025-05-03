<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntryParticipant;
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
/* @var CalendarEntryParticipant[] $externalParticipants */
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

    <?php if (!empty($externalParticipants)): ?>
        <p class="calendar-entry-participants-count"><?= Yii::t('CalendarModule.views', '{count} External Participants', ['count' => '<span>' . count($externalParticipants) . '</span>']) ?></p>

        <?= Html::beginTag('ul', ['class' => 'media-list']) ?>
        <?php foreach ($externalParticipants as $externalParticipant) : ?>
            <?= Html::beginTag('li', ['data-user-id' => $user->id]) ?>
            <div class="media">
                <div class="media-body">
                    <h4 class="media-heading"><?= Html::encode($externalParticipant->external_user_email) ?></h4>
                </div>
                <div class="media-body">
                    <span class="label label-default"><?= ParticipantItem::getStatusTitle($externalParticipant->participation_state) ?></span>
                </div>
            </div>
            <?= Html::endTag('li') ?>
        <?php endforeach; ?>
        <?= Html::endTag('ul') ?>
    <?php endif; ?>
<?= Html::endTag('div') ?>
