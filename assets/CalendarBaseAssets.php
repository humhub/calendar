<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use humhub\modules\calendar\interfaces\event\CalendarEntryTypeSetting;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\ui\view\components\View;
use yii\web\AssetBundle;
use Yii;

class CalendarBaseAssets extends AssetBundle
{
    public $defer = true;

    public $publishOptions = [
        'forceCopy' => false,
    ];

    public $sourcePath = '@calendar/resources';

    public $css = [
        'css/calendar.min.css',
    ];

    public $js = [
        'js/humhub.calendar.min.js',
    ];

    /**
     * @param View $view
     * @return AssetBundle
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
