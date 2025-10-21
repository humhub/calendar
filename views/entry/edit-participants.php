<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\helpers\Html;
use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\calendar\widgets\ParticipantList;
use humhub\widgets\form\ActiveForm;

/* @var $form ActiveForm */
/* @var $calendarEntryParticipationForm CalendarEntryParticipationForm */
/* @var $renderWrapper bool */
?>
<?= $renderWrapper ? Html::beginTag('div', ['class' => 'calendar-entry-participants']) : '' ?>
    <?= ParticipantList::widget([
        'form' => $form,
        'model' => $calendarEntryParticipationForm,
    ]) ?>
<?= $renderWrapper ? Html::endTag('div') : '' ?>
