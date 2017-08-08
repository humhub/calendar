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

class GlobalConfigMenu extends SettingsTabs
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->items = [
            [
                'label' => Yii::t('CalendarModule.widgets_GlobalConfigMenu', 'Defaults'),
                'url' => Url::toRoute(['/calendar/config/index']),
                'active' => $this->isCurrentRoute('calendar', 'config', 'index')
            ],
            [
                'label' => Yii::t('CalendarModule.widgets_GlobalConfigMenu', 'Event Types'),
                'url' => Url::toRoute(['/calendar/config/types']),
                'active' => $this->isCurrentRoute('calendar', 'config', 'types')
            ],
            [
                'label' => Yii::t('CalendarModule.widgets_GlobalConfigMenu', 'Snippet'),
                'url' => Url::toRoute(['/calendar/config/snippet']),
                'active' => $this->isCurrentRoute('calendar', 'config', 'snippet')
            ]
        ];
        parent::init();
    }

}