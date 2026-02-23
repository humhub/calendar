<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use humhub\components\assets\AssetBundle;

class FullCalendarAssets extends AssetBundle
{
    public $forceCopy = false;

    public $sourcePath = '@calendar/resources';

    public $css = ['css/fullcalendar.bundle.min.css'];

    public $js = ['js/fullcalendar.bundle.min.js'];
}
