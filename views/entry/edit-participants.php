<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\libs\Html;
use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\calendar\widgets\ParticipantList;
use humhub\modules\ui\form\widgets\ActiveForm;

/* @var $form ActiveForm */
/* @var $calendarEntryParticipationForm CalendarEntryParticipationForm */
/* @var $renderWrapper bool */
?>
<?= $renderWrapper ? Html::beginTag('div', ['class' => 'modal-body calendar-entry-participants']) : '' ?>
    <?= ParticipantList::widget([
        'form' => $form,
        'model' => $calendarEntryParticipationForm,
    ]) ?>
<?= $renderWrapper ? Html::endTag('div') : '' ?>