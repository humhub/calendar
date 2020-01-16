<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\modules\calendar\widgets\mails\CalendarEventMailInfo;
use humhub\modules\calendar\models\CalendarEntry;

/* @var $this yii\web\View */
/* @var $viewable humhub\modules\content\notifications\ContentCreated */
/* @var $url string */
/* @var $date string */
/* @var $isNew boolean */
/* @var $isNew boolean */
/* @var $originator \humhub\modules\user\models\User */
/* @var $source yii\db\ActiveRecord */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
/* @var $space humhub\modules\space\models\Space */
/* @var $record \humhub\modules\notification\models\Notification */
/* @var $html string */
/* @var $text string */

$extraInfo = null;
if($source instanceof CalendarEntry) {
    $extraInfo = $source->participant_info;
}

?>
<?php $this->beginContent('@notification/views/layouts/mail.php', $_params_); ?>

    <?=  CalendarEventMailInfo::html($source, $url, $extraInfo) ?>

<?php $this->endContent();
