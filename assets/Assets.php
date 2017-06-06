<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $publishOptions = [
        'forceCopy' => false
    ];
    
    public $sourcePath = '@calendar/resources';

    public $css = [
        'js/fullcalendar/fullcalendar.min.css',
        'css/calendar.css',
        //'fullcalendar/fullcalendar.print.css', // print
    ];
    public $js = [
        'js/fullcalendar/lib/moment.min.js',
        'js/fullcalendar/fullcalendar.min.js',
        'js/fullcalendar/locale-all.js',
        'js/humhub.calendar.js'
    ];
}
