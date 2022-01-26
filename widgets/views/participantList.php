<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\user\models\User as User;
use humhub\modules\user\widgets\Image;
use humhub\widgets\AjaxLinkPager;
use yii\data\Pagination;
use yii\helpers\Html;

/* @var CalendarEntry $entry */
/* @var User[] $users */
/* @var Pagination $pagination */
?>

<?php if (empty($users)): ?>
    <p><?= Yii::t('CalendarModule.views_entry_edit', 'No participants.'); ?></p>
<?php endif; ?>

<ul class="media-list">
    <?php foreach ($users as $user) : ?>
        <li>
            <a href="<?= $user->getUrl(); ?>" data-modal-close="1">
                <div class="media">
                    <?= Image::widget([
                        'user' => $user,
                        'link' => false,
                        'width' => 32,
                        'htmlOptions' => ['class' => 'media-object pull-left'],
                    ]) ?>

                    <div class="media-body">
                        <h4 class="media-heading"><?= Html::encode($user->displayName); ?></h4>
                        <h5><?= Html::encode($user->displayNameSub); ?></h5>
                    </div>
                </div>
            </a>
            <div>
                <?php // TODO: state switcher: Html::dropDownList('state', '', [])?>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

<div class="pagination-container">
    <?= AjaxLinkPager::widget(['pagination' => $pagination]); ?>
</div>


