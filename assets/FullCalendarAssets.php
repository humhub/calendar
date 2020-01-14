<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use yii\web\AssetBundle;

class FullCalendarAssets extends AssetBundle
{
    public $publishOptions = [
        'forceCopy' => false
    ];
    
    public $sourcePath = '@calendar/node_modules/@fullcalendar';

    public $css = [
        'core/main.min.css',
        'daygrid/main.min.css',
        'timegrid/main.min.css',
        'list/main.min.css',
    ];

    public $js = [
        'core/main.min.js',
        'core/locales-all.min.js',
        'daygrid/main.min.js',
        'timegrid/main.min.js',
        'list/main.min.js',
        'interaction/main.min.js',
        'moment/main.js',
    ];

    public $depends = [
        MomentAsset::class
    ];

}
