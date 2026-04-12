<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use humhub\modules\calendar\models\participation\FullCalendarSettings;
use Yii;
use yii\web\AssetBundle;

class CalendarAsset extends AssetBundle
{
    public $defer = true;

    public $publishOptions = [
        'forceCopy' => false,
    ];

    public $sourcePath = '@calendar/resources/js';

    public $js = [
        'humhub.calendar.Calendar.min.js',
    ];

    public $depends = [
        FullCalendarAssets::class,
        CalendarBaseAssets::class,
    ];

    /**
     * @inheritdoc
     */
    public static function registerForContainer($view, $contentContainer = null)
    {
        $view->registerJsConfig('calendar.Calendar', [
            'listViewType' => (new FullCalendarSettings(['contentContainer' => $contentContainer]))->listViewType,
        ]);
        return parent::register($view);
    }
}
