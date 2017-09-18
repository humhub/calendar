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
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\widgets\SettingsTabs;

class ContainerConfigMenu extends SettingsTabs
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $contentContainer = Yii::$app->controller->contentContainer;

        /* @var $calendarService CalendarService */
        $calendarService =  Yii::$app->getModule('calendar')->get(CalendarService::class);

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
            ],
        ];

        if(!empty($calendarService->getCalendarItemTypes($contentContainer))) {
            $this->items[] = [
                'label' => Yii::t('CalendarModule.widgets_GlobalConfigMenu', 'Other Calendars'),
                'url' => $contentContainer->createUrl('/calendar/container-config/calendars'),
                'active' => $this->isCurrentRoute('calendar', 'container-config', 'calendars')
            ];
        }

        parent::init();
    }

}