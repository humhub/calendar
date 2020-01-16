<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use yii\web\AssetBundle;

class MomentTimezoneAsset extends AssetBundle
{
    public $publishOptions = [
        'forceCopy' => false
    ];
    
    public $sourcePath = '@calendar/node_modules/moment-timezone/builds';

    public $js = [
        'moment-timezone-with-data.js',
    ];

    public $depends = [
        MomentAsset::class
    ];

}
