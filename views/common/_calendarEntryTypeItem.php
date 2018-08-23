<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */
use humhub\libs\Html;
use humhub\widgets\ModalButton;
use yii\helpers\Url;

/* @var $this \humhub\components\View */
/* @var $model \humhub\modules\calendar\models\CalendarEntryType */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */


if($contentContainer) {
    $editUrl = $contentContainer->createUrl('/calendar/container-config/edit-type', ['id' => $model->id]);
    $deleteUrl = $contentContainer->createUrl('/calendar/container-config/delete-type', ['id' => $model->id]);
} else {
    $editUrl = URL::to(['/calendar/config/edit-type', 'id' => $model->id]);
    $deleteUrl = URL::to(['/calendar/config/delete-type', 'id' => $model->id]);
}

$isSpaceGlobal = ($contentContainer) && !$model->contentContainer;

?>

<?= $this->render('_calendarTypeItem', ['editUrl' => $editUrl, 'color' => $model->color, 'deleteUrl' => $deleteUrl, 'title' => $model->name, 'disabled' => false, 'isSpaceGlobal' => $isSpaceGlobal])?>