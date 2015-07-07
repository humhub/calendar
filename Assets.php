<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace module\calendar;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{

    public $sourcePath = '@module/calendar/assets';
    public $css = [
        'fullcalendar/fullcalendar.css',
            //'fullcalendar/fullcalendar.print.css', // print
    ];
    public $js = [
        'fullcalendar/lib/moment.min.js',
        'fullcalendar/lib/jquery-ui.custom.min.js',
        'fullcalendar/fullcalendar.min.js',
        'fullcalendar/lang-all.js',
        'fullcalendar.js'
    ];

}
