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


use humhub\widgets\SettingsTabs;
use Yii;
use humhub\widgets\BaseMenu;
use yii\helpers\Url;

class ContainerConfigMenu extends SettingsTabs
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        $contentContainer = Yii::$app->controller->contentContainer;

        $this->items = [
            [
                'label' => Yii::t('CalendarModule.widgets_GlobalConfigMenu', 'Defaults'),
                'url' => $contentContainer->createUrl('/calendar/container-config/index'),
                'active' => $this->isCurrentRoute('calendar', 'container-config', 'index')
            ],
            [
                'label' => Yii::t('CalendarModule.widgets_GlobalConfigMenu', 'Event Types'),
                'url' => $contentContainer->createUrl('/calendar/container-config/types'),
                'active' => $this->isCurrentRoute('calendar', 'container-config', 'types')
            ]
        ];
        parent::init();
    }

}