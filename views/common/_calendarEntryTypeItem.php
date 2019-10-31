<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\calendar\helpers\Url;

/* @var $this \humhub\components\View */
/* @var $model \humhub\modules\calendar\models\CalendarEntryType */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */

$editUrl = Url::toEditType($model, $contentContainer);
$deleteUrl = Url::toDeleteType($model,$contentContainer);
$isSpaceGlobal = $contentContainer && !$model->contentContainer;

?>

<?= $this->render('_calendarTypeItem', ['editUrl' => $editUrl, 'color' => $model->color, 'deleteUrl' => $deleteUrl, 'title' => $model->name, 'disabled' => false, 'isSpaceGlobal' => $isSpaceGlobal])?>