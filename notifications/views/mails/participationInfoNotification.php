<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\components\View;
use humhub\modules\calendar\widgets\mails\CalendarEventMailInfo;
use humhub\modules\calendar\models\CalendarEntry;
use yii\db\ActiveRecord;

/* @var $this View */
/* @var $url string */
/* @var $source ActiveRecord */

$extraInfo = $source instanceof CalendarEntry ? $source->participant_info : null;
?>
<?php $this->beginContent('@notification/views/layouts/mail.php') ?>

    <?= CalendarEventMailInfo::html($source, $url, $extraInfo) ?>

<?php $this->endContent();
