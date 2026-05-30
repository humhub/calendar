<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use humhub\components\assets\AssetBundle;
use humhub\modules\calendar\interfaces\event\CalendarEntryTypeSetting;
use humhub\modules\calendar\models\CalendarEntryType;
use Yii;

class CalendarBaseAssets extends AssetBundle
{
    public $forceCopy = false;

    public $sourcePath = '@calendar/resources';

    public $css = [
        'css/humhub.calendar.min.css',
    ];

    public $js = [
        'js/humhub.calendar.min.js',
    ];

    /**
     * @inheritdoc
     */
    public static function register($view)
    {
        // set defaultEventColor if we are in a content container
        if (isset(Yii::$app->controller->contentContainer)) {
            $container = Yii::$app->controller->contentContainer;
            $defaultEventColor = (new CalendarEntryTypeSetting(['type' => new CalendarEntryType(), 'contentContainer' => $container]))->getColor();

            $view->registerJsConfig('calendar', [
                'defaultEventColor' => $defaultEventColor,
            ]);
        }
        return parent::register($view);
    }
}
