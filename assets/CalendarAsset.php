<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use humhub\modules\ui\view\components\View;
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
     * @param View $view
     * @return AssetBundle
     */
    public static function register($view)
    {
        $view->registerJsConfig('calendar.Calendar', [
            'text' => [
                'button.today' => Yii::t('CalendarModule.base', 'Today'),
                'button.month' => Yii::t('CalendarModule.base', 'Month'),
                'button.week' => Yii::t('CalendarModule.base', 'Week'),
                'button.day' => Yii::t('CalendarModule.base', 'Day'),
                'button.list' => Yii::t('CalendarModule.base', 'List'),
            ],
        ]);
        return parent::register($view);
    }

}
