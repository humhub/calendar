<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\web\View;

/* @var $this View */
/* @var $model CalendarEntryType */
/* @var $contentContainer ContentContainerActiveRecord */
?>
<?= $this->render('_calendarTypeItem', [
    'editUrl' => Url::toEditType($model, $contentContainer),
    'color' => $model->color,
    'deleteUrl' => Url::toDeleteType($model, $contentContainer),
    'title' => $model->name,
    'disabled' => false,
    'isSpaceGlobal' => $contentContainer && !$model->contentContainer,
]) ?>
