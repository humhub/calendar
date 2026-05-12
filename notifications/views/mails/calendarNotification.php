<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\components\View;
use humhub\modules\calendar\widgets\mails\CalendarEventMailInfo;
use yii\db\ActiveRecord;

/* @var $this View */
/* @var $url string */
/* @var $source ActiveRecord */
?>
<?php $this->beginContent('@notification/views/layouts/mail.php') ?>

    <?= CalendarEventMailInfo::html($source, $url) ?>

<?php $this->endContent();
