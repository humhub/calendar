<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use humhub\components\assets\AssetBundle;
use humhub\modules\calendar\models\participation\FullCalendarSettings;
use Yii;

class CalendarAsset extends AssetBundle
{
    public $forceCopy = false;

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
            'text' => [
                'button.today' => Yii::t('CalendarModule.base', 'Today'),
                'button.month' => Yii::t('CalendarModule.base', 'Month'),
                'button.week' => Yii::t('CalendarModule.base', 'Week'),
                'button.day' => Yii::t('CalendarModule.base', 'Day'),
                'button.list' => Yii::t('CalendarModule.base', 'List'),
            ],
            'listViewType' => (new FullCalendarSettings(['contentContainer' => $contentContainer]))->listViewType,
        ]);
        return parent::register($view);
    }
}
