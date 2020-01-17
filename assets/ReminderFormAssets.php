<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use yii\web\AssetBundle;

class ReminderFormAssets extends AssetBundle
{
    public $publishOptions = [
        'forceCopy' => false
    ];
    
    public $sourcePath = '@calendar/resources';

    public $js = [
        'js/humhub.calendar.reminder.Form.min.js',
    ];
}
