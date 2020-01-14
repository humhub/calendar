<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use yii\web\AssetBundle;

class FullCalendarThemeAssets extends AssetBundle
{
    public $publishOptions = [
        'forceCopy' => false
    ];
    
    public $sourcePath = '@calendar/resources/js/theme/bootstrap';

    public $css = [
        'main.min.css'
    ];
    public $js = [
        'main.min.js'
    ];
    public $depends = [
        FullCalendarAssets::class
    ];

}
