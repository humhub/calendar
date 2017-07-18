<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 17.07.2017
 * Time: 21:02
 */

namespace humhub\modules\calendar\widgets;


use Yii;
use humhub\widgets\BaseMenu;
use yii\helpers\Url;

class GlobalConfigMenu extends BaseMenu
{

    public $template = "@humhub/widgets/views/tabMenu";
    public $type = "adminUserSubNavigation";

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->addItem([
            'label' => Yii::t('CalendarModule.widgets_GlobalConfigMenu', 'Defaults'),
            'url' => Url::toRoute(['/calendar/config/index']),
            'sortOrder' => 100,
            'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'calendar' && Yii::$app->controller->id == 'config' && Yii::$app->controller->action->id == 'index')
        ]);

        $this->addItem([
            'label' => Yii::t('CalendarModule.widgets_GlobalConfigMenu', 'Snippet'),
            'url' => Url::toRoute(['/calendar/config/snippet']),
            'sortOrder' => 200,
            'isActive' => (Yii::$app->controller->module && Yii::$app->controller->module->id == 'calendar' && Yii::$app->controller->id == 'config' && Yii::$app->controller->action->id == 'snippet')
        ]);
    }

}