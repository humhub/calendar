<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use yii\web\AssetBundle;

class CalendarBaseAssets extends AssetBundle
{
    public $publishOptions = [
        'forceCopy' => false
    ];
    
    public $sourcePath = '@calendar/resources';

    public $css = [
        'css/calendar.css',
    ];
    public $js = [
        'js/humhub.calendar.min.js'
    ];
}
