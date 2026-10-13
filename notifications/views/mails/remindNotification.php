<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use humhub\components\View;
use humhub\modules\calendar\interfaces\reminder\CalendarEventReminderIF;
use humhub\modules\calendar\widgets\mails\CalendarEventMailInfo;
use humhub\modules\notification\models\Notification;

/* @var $this View */
/* @var $url string */
/* @var $record Notification */
/* @var $source CalendarEventReminderIF */

?>
<?php $this->beginContent('@notification/views/layouts/mail.php') ?>

    <?= CalendarEventMailInfo::html($source, $url, null, $record->user) ?>

<?php $this->endContent();
